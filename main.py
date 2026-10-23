#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
ESP32 GIẢ LẬP + GIAO DIỆN — MỘT FILE DUY NHẤT (Aquaponic AI IoT)
=================================================================
Thay thế người làm phần cứng để test toàn bộ web:

  Cảm biến  -> aquaponic/sensor/data      (kéo thanh trượt / chọn kịch bản / tự gửi)
  Thiết bị  -> aquaponic/device/status    (bấm công tắc = "nút vật lý")
  Lệnh      <- aquaponic/control          (từ Dashboard hoặc AI Web, ESP32 giả lập tự làm theo)
  AI Edge   -> aquaponic/ai/result/<id>   + aquaponic/ai/alert
  Tab "Kiểm tra tự động AI Web": chạy 19 kịch bản và báo ĐẠT / SAI

CÁCH CHẠY (Windows):
  1) Lưu file này bằng Notepad: Save As -> tên  esp32_sim.py  -> ô Encoding chọn  UTF-8
  2) Mở CMD:   python -m pip install "paho-mqtt>=2.0"
  3) Chạy:     python esp32_sim.py            (hoặc thêm  --autoconnect  để tự kết nối)

Mặc định dùng HiveMQ Cloud của project: cổng 8883, bật TLS.
Broker nội bộ (Mosquitto): cổng 1883, bỏ tích TLS, để trống tài khoản.
"""
import collections
import datetime
import json
import os
import random
import ssl
import sys
import threading
import time


try:
    import paho.mqtt.client as mqtt
except ImportError:
    sys.exit('Thiếu thư viện MQTT. Mở CMD và chạy:  python -m pip install "paho-mqtt>=2.0"')

# ════════════════════════════════════════════════════════════════════════════
# 1. TOPIC — đúng như Frontend/js/config.js, mqtt_subscriber.php, ai_mqtt_subscriber.php
# ════════════════════════════════════════════════════════════════════════════
TOPIC_SENSOR = "aquaponic/sensor/data"       # ESP32 -> web
TOPIC_STATUS = "aquaponic/device/status"     # ESP32 -> web
TOPIC_CONTROL = "aquaponic/control"          # web  -> ESP32
TOPIC_AI_RESULT = "aquaponic/ai/result/{id}"          # ESP32 (AI Edge) -> web
TOPIC_AI_WEB_RESULT = "aquaponic/ai/web/result/{id}"      # AI Web -> web
TOPIC_AI_WEB_ENABLE = "aquaponic/ai/web/enable"          # web/main.py -> Bật/Tắt AI Web
TOPIC_AI_ALERT = "aquaponic/ai/alert"            # ESP32 -> ai_mqtt_subscriber (chỉ in console)
TOPIC_ALL = "aquaponic/#"

DEVICES = ["maybom", "denled", "quatgio"]
DEVICE_VI = {"maybom": "Máy bơm", "denled": "Đèn LED", "quatgio": "Quạt gió"}

# ════════════════════════════════════════════════════════════════════════════
# 2. NGƯỠNG — ưu tiên đọc AI_Web/thresholds.json (cùng nguồn với AI Web), nếu không có thì dùng bản này
# ════════════════════════════════════════════════════════════════════════════
DEFAULT_THRESH = {
    "case_trigger": {"min_abnormal_sensors": 2},
    "sensors": {
        "nhiet_do": {"label": "Nhiệt độ", "unit": "°C", "breaks": [28, 32], "fault_at_or_below": 0},
        "do_am": {"label": "Độ ẩm", "unit": "%", "breaks": [55, 75], "fault_at_or_below": 0},
        "anh_sang": {"label": "Ánh sáng", "unit": "", "breaks": [367, 633], "fault_at_or_below": 0},
        "muc_nuoc": {"label": "Mực nước", "unit": "", "breaks": [733, 1467], "fault_at_or_below": 0},
    },
    "phan_tich_mac_dinh": {
        "nhiet_do": {"low": None,
                     "high": {"severity": "danger", "title": "Nhiệt độ cao", "device": "quatgio", "command": "ON", "reason": "Nhiệt độ cao, bật quạt hạ nhiệt"},
                     "fault": {"severity": "warning", "title": "Cảm biến nhiệt lỗi", "device": "quatgio", "command": "ON", "reason": "Cảm biến nhiệt lỗi, bật quạt an toàn"}},
        "do_am": {"low": None,
                  "high": {"severity": "warning", "title": "Độ ẩm cao", "device": "quatgio", "command": "ON", "reason": "Độ ẩm cao, bật quạt thông gió"},
                  "fault": {"severity": "warning", "title": "Cảm biến độ ẩm lỗi", "device": None, "command": None, "reason": "Cảm biến độ ẩm lỗi, bỏ qua độ ẩm"}},
        "anh_sang": {"low": {"severity": "warning", "title": "Thiếu sáng", "device": "denled", "command": "ON", "reason": "Thiếu sáng, bật đèn LED"},
                     "high": None,
                     "fault": {"severity": "warning", "title": "Cảm biến sáng lỗi", "device": "denled", "command": "OFF", "reason": "Cảm biến sáng lỗi, tắt đèn"}},
        "muc_nuoc": {"low": {"severity": "warning", "title": "Mực nước thấp", "device": "maybom", "command": "ON", "reason": "Mực nước thấp, bật bơm"},
                     "high": {"severity": "warning", "title": "Nước đầy", "device": "maybom", "command": "OFF", "reason": "Nước đầy, không bơm"},
                     "fault": {"severity": "warning", "title": "Cảm biến nước lỗi", "device": "maybom", "command": "OFF", "reason": "Cảm biến nước lỗi, tắt bơm"}},
    },
}


def load_thresholds(path=None):
    here = os.path.dirname(os.path.abspath(__file__))
    candidates = [path] if path else [
        os.path.join(here, "..", "AI_Web", "thresholds.json"),
        os.path.join(here, "thresholds.json"),
    ]
    for p in candidates:
        if p and os.path.isfile(p):
            with open(p, encoding="utf-8") as f:
                return json.load(f), os.path.normpath(p)
    return DEFAULT_THRESH, "(bản nhúng sẵn)"


SENSORS = ["nhiet_do", "do_am", "anh_sang", "muc_nuoc"]


def get_level(cfg, sensor, value):
    s = cfg["sensors"][sensor]
    if value <= s["fault_at_or_below"]:
        return "fault"
    return ["low", "mid", "high"][sum(1 for b in s["breaks"] if value >= b)]


def analyze_l1(cfg, v):
    """Bản Python của lớp 1 (engine.php): dùng cho AI Edge giả lập và để biết web PHẢI phản ứng thế nào."""
    levels, abn, cmds, no_default = {}, {}, {}, []
    parts, severity, fault = [], "warning", False
    reasons = {}
    for k in SENSORS:
        lv = get_level(cfg, k, v[k])
        levels[k] = lv
        if lv == "mid":
            continue
        pt = cfg["phan_tich_mac_dinh"][k].get(lv)
        abn[k] = lv
        if pt is None:
            no_default.append(k)
        title = pt["title"] if pt else {"low": "Thấp", "high": "Cao"}[lv]
        parts.append(f'{cfg["sensors"][k]["label"]}: {v[k]:g}{cfg["sensors"][k]["unit"]} ({title})')
        if lv == "fault":
            fault = True
        if pt:
            if pt["severity"] == "danger":
                severity = "danger"
            if pt["device"] and pt["command"]:
                prio = 2 if lv == "fault" else 1
                if pt["device"] not in cmds or prio > cmds[pt["device"]][1]:
                    cmds[pt["device"]] = (pt["command"], prio)
                    reasons[pt["device"]] = pt["reason"]
    n_min = cfg.get("case_trigger", {}).get("min_abnormal_sensors", 2)
    call_l2 = bool(abn) and (len(abn) >= n_min or bool(no_default))
    action = None
    for dev in ("maybom", "quatgio", "denled"):
        if dev in cmds:
            action = {"device": dev, "command": cmds[dev][0], "reason": reasons[dev]}
            break
    return {
        "levels": levels, "abnormal": abn, "commands": {d: c[0] for d, c in cmds.items()},
        "call_l2": call_l2, "severity": severity if abn else "normal", "fault": fault,
        "summary": " | ".join(parts) if parts else "Tất cả chỉ số đang trong ngưỡng bình thường.",
        "action": action, "reasons": reasons,
    }


# ════════════════════════════════════════════════════════════════════════════
# 3. KỊCH BẢN CẢM BIẾN — (nhiet_do, do_am, anh_sang, muc_nuoc)
#    Giá trị chọn cách xa mốc ngưỡng để kết quả không bị nhiễu.
# ════════════════════════════════════════════════════════════════════════════
SCENARIOS = collections.OrderedDict([
    ("binh_thuong", ((30, 65, 500, 1100), "Mọi chỉ số bình thường")),
    ("nhiet_cao", ((34, 65, 500, 1100), "Nhiệt cao -> quạt BẬT")),
    ("nhiet_thap", ((26, 65, 500, 1100), "Nhiệt thấp (ngưỡng mặc định chưa có phân tích -> lớp 2 Python)")),
    ("do_am_cao", ((30, 82, 500, 1100), "Độ ẩm cao -> quạt BẬT")),
    ("do_am_thap", ((30, 45, 500, 1100), "Độ ẩm thấp (chưa có phân tích mặc định -> lớp 2)")),
    ("thieu_sang", ((30, 65, 200, 1100), "Thiếu sáng -> đèn BẬT")),
    ("sang_manh", ((30, 65, 800, 1100), "Nắng gắt (nhiệt vừa + sáng mạnh) -> lớp 2 gợi ý quạt")),
    ("nuoc_thap", ((30, 65, 500, 500), "Mực nước thấp -> bơm BẬT")),
    ("nuoc_day", ((30, 65, 500, 1800), "Nước đầy -> bơm TẮT")),
    ("nong_kho", ((34, 45, 500, 1100), "Nóng + khô -> lớp 2 gợi ý bơm tưới hạ nhiệt")),
    ("nong_toi", ((34, 65, 200, 1100), "Nóng + tối -> mặc định bật đèn, lớp 2 khuyến nghị KHÔNG bật")),
    ("mat_am", ((26, 80, 500, 1100), "Mát + ẩm -> lớp 2 gợi ý bật đèn cho ấm và khô")),
    ("nong_kho_toi", ((34, 40, 200, 1000), "Nóng + khô + tối (3 cảm biến lệch)")),
    ("am_cao_nuoc_thap", ((30, 80, 500, 500), "Ẩm cao + nước thấp (2 cảm biến lệch)")),
    ("nguy_hiem", ((35, 90, 900, 2200), "Mọi chỉ số ở mức cao nhất")),
    ("loi_nhiet", ((0, 65, 500, 1100), "Cảm biến nhiệt lỗi (đọc 0) -> quạt BẬT an toàn")),
    ("loi_do_am", ((30, 0, 500, 1100), "Cảm biến độ ẩm lỗi (đọc 0)")),
    ("loi_anh_sang", ((30, 65, 0, 1100), "Cảm biến ánh sáng lỗi (đọc 0) -> đèn TẮT")),
    ("loi_muc_nuoc", ((30, 65, 500, 0), "Cảm biến mực nước lỗi (đọc 0) -> bơm TẮT")),
])
NOISE = {"nhiet_do": 0.3, "do_am": 1.0, "anh_sang": 8.0, "muc_nuoc": 15.0}


def vals_from_tuple(t):
    return dict(zip(SENSORS, [float(x) for x in t]))


# ════════════════════════════════════════════════════════════════════════════
# 4. ESP32 GIẢ LẬP
# ════════════════════════════════════════════════════════════════════════════
C = {"r": "\033[0m", "g": "\033[32m", "y": "\033[33m", "c": "\033[36m", "m": "\033[35m", "d": "\033[90m", "R": "\033[31m"}


def ts():
    return datetime.datetime.now().strftime("%H:%M:%S")


class ESP32:
    def __init__(self, a):
        self.a = a
        self.device_id = str(a.device_id)
        self.cfg, self.cfg_src = load_thresholds(a.thresholds)
        self.lock = threading.RLock()
        self.state = {d: 0 for d in DEVICES}
        self.mode = "AUTO"
        self.manual_until = 0.0
        self.target = vals_from_tuple(SCENARIOS["binh_thuong"][0])
        self.last_values = dict(self.target)
        self.edge_ai = a.edge_ai
        self.auto_send = False
        self.interval = a.interval
        self.noise = True
        self.running = True
        self.echo = collections.deque(maxlen=200)       # (topic, payload) mình vừa gửi -> bỏ qua khi nhận lại
        self.observed = []                               # (time, topic, payload_str) — dùng cho bộ test
        self.obs_lock = threading.Lock()
        self.connected = threading.Event()
        self.on_event = None                             # giao diện (GUI) đăng ký hàm nhận sự kiện: on_event(kind, topic, payload)

        self.cli = mqtt.Client(mqtt.CallbackAPIVersion.VERSION2, client_id=f"esp32_sim_{random.randrange(16**6):06x}")
        if a.user:
            self.cli.username_pw_set(a.user, a.password)
        if not a.no_tls:
            self.cli.tls_set(cert_reqs=ssl.CERT_NONE if a.insecure else ssl.CERT_REQUIRED)
            if a.insecure:
                self.cli.tls_insecure_set(True)
        self.cli.on_connect = self.on_connect
        self.cli.on_disconnect = self.on_disconnect
        self.cli.on_message = self.on_message

    # ── kết nối ──
    def start(self):
        self.cli.connect(self.a.host, self.a.port, keepalive=60)
        self.cli.loop_start()
        if not self.connected.wait(15):
            self.cli.loop_stop()
            raise ConnectionError(f"Không kết nối được broker {self.a.host}:{self.a.port} (kiểm tra mạng/tài khoản)")
        threading.Thread(target=self._ticker, daemon=True).start()

    def on_connect(self, cli, ud, flags, rc, props=None):
        if rc != 0:
            print(f"{C['R']}❌ Kết nối thất bại: {rc}{C['r']}")
            return
        cli.subscribe(TOPIC_ALL, qos=0)
        self.connected.set()
        print(f"{C['g']}[{ts()}] ✅ ESP32 giả lập đã kết nối {self.a.host}:{self.a.port}, subscribe {TOPIC_ALL}{C['r']}")

    def on_disconnect(self, cli, ud, flags, rc, props=None):
        print(f"{C['y']}[{ts()}] ⚠️ Mất kết nối MQTT ({rc}), paho tự kết nối lại...{C['r']}")

    # ── gửi ──
    def pub(self, topic, obj, label=""):
        payload = obj if isinstance(obj, str) else json.dumps(obj, ensure_ascii=False)
        self.echo.append((topic, payload))
        self.cli.publish(topic, payload, qos=0)
        if self.on_event:
            self.on_event("tx", topic, payload)
        if not self.a.quiet:
            print(f"{C['c']}[{ts()}] 📤 {label or 'PUBLISH'} {topic}{C['r']}\n      {payload}")

    def publish_sensor(self, values=None):
        v = dict(values or self.last_values)
        self.last_values = dict(v)
        payload = {
            "nhiet_do": round(v["nhiet_do"], 1), "do_am": round(v["do_am"], 1),
            "anh_sang": round(v["anh_sang"], 2), "muc_nuoc": int(round(v["muc_nuoc"])),
        }
        self.pub(TOPIC_SENSOR, payload, "CẢM BIẾN")
        return payload

    def publish_status(self):
        with self.lock:
            payload = {d: int(self.state[d]) for d in DEVICES}
        self.pub(TOPIC_STATUS, payload, "TRẠNG THÁI THIẾT BỊ")

    def time_window(self):
        return "day" if 6 <= datetime.datetime.now().hour < 18 else "night"

    def build_ai_result(self, values, res, action=None, mode=None, revert=0, summary=None, reco=None):
        mode = mode or self.mode
        sev = res["severity"]
        body = {
            "device_id": self.device_id,
            "timestamp": datetime.datetime.now().astimezone().isoformat(timespec="seconds"),
            "mode": mode,
            "auto_revert_in_seconds": int(revert),
            "sensor_values": {"nhietdo": values["nhiet_do"], "do_am": values["do_am"],
                              "muc_nuoc": int(values["muc_nuoc"]), "anhsang": values["anh_sang"]},
            "ai_result": {
                "engine": "edge_threshold_v1", "time_window": self.time_window(),
                "anomaly_type": "none" if not res["abnormal"] else ("sensor_fault" if res["fault"] else "environment"),
                "severity": sev, "confidence": "certain",
            },
            "analysis": {"summary": summary or res["summary"],
                         "abnormal_sensors": [{"nhiet_do": "nhietdo", "do_am": "do_am", "anh_sang": "anhsang", "muc_nuoc": "muc_nuoc"}[k] for k in res["abnormal"]]},
            "recommendation": reco or (
                {"action": "NONE", "message": "Không cần can thiệp, hệ thống đang ổn định."} if not res["abnormal"]
                else {"action": "CHECK_SENSOR" if res["fault"] else "CHECK_ENVIRONMENT",
                      "message": (action or {}).get("reason", "Theo dõi thêm các chỉ số đang lệch ngưỡng.")}),
        }
        if action:
            body["action_taken"] = action
        return body

    def publish_ai_result(self, values=None, action=None, summary=None, reco=None):
        if not self.edge_ai:                 # AI IoT đang TẮT: không phát kết quả AI Edge nào
            return None
        v = values or self.last_values
        res = analyze_l1(self.cfg, v)
        with self.lock:
            revert = max(0, int(self.manual_until - time.time())) if self.mode == "MANUAL" else 0
        body = self.build_ai_result(v, res, action=action, revert=revert, summary=summary, reco=reco)
        self.pub(TOPIC_AI_RESULT.format(id=self.device_id), body, "AI EDGE")
        return body

    # ── AI Edge: phân tích + tự điều khiển (khi AUTO) ──
    def edge_step(self, values):
        res = analyze_l1(self.cfg, values)
        with self.lock:
            auto = self.mode == "AUTO"
        action = None
        changed = False
        if auto and res["commands"]:
            with self.lock:
                for dev, cmd in res["commands"].items():
                    want = 1 if cmd == "ON" else 0
                    if self.state[dev] != want:
                        self.state[dev] = want
                        changed = True
                        action = action or {"device": dev, "command": cmd, "reason": res["reasons"][dev]}
        if changed:
            self.publish_status()
        body = self.build_ai_result(values, res, action=action)
        self.pub(TOPIC_AI_RESULT.format(id=self.device_id), body, "AI EDGE")
        if res["severity"] == "danger":
            self.pub(TOPIC_AI_ALERT, f"[{self.device_id}] NGUY HIỂM: {res['summary']}", "CẢNH BÁO AI")

    # ── nhận ──
    def on_message(self, cli, ud, msg):
        payload = msg.payload.decode("utf-8", "replace")
        key = (msg.topic, payload)
        is_echo = key in self.echo
        if is_echo:
            try:
                self.echo.remove(key)
            except ValueError:
                pass
        else:
            with self.obs_lock:
                self.observed.append((time.time(), msg.topic, payload))
            self._show_incoming(msg.topic, payload)
            if self.on_event:
                self.on_event("rx", msg.topic, payload)
        if msg.topic == TOPIC_CONTROL and not is_echo:
            self.handle_control(payload)

    def _show_incoming(self, topic, payload):
        extra = ""
        if topic.startswith("aquaponic/ai/result/") or topic.startswith("aquaponic/ai/web/result/"):
            try:
                d = json.loads(payload)
                extra = f"  {C['m']}[engine={d.get('ai_result', {}).get('engine')}, mức={d.get('ai_result', {}).get('severity')}]{C['r']}"
            except Exception:
                pass
        if not self.a.quiet:
            print(f"{C['y']}[{ts()}] 📩 NHẬN {topic}{C['r']}{extra}\n      {payload[:600]}")

    def handle_control(self, payload):
        """Lệnh từ Dashboard (nút bấm) hoặc AI Web:  {"quatgio": 1}  ->  bật/tắt thiết bị rồi báo lại trạng thái."""
        try:
            cmd = json.loads(payload)
        except json.JSONDecodeError:
            print(f"{C['R']}   ↳ lệnh không phải JSON hợp lệ, bỏ qua{C['r']}")
            return
        applied = {}
        with self.lock:
            for dev in DEVICES:
                if dev in cmd:
                    try:
                        self.state[dev] = 1 if int(cmd[dev]) else 0
                        applied[dev] = self.state[dev]
                    except (TypeError, ValueError):
                        pass
            if applied and self.a.manual_seconds > 0:
                self.mode = "MANUAL"
                self.manual_until = time.time() + self.a.manual_seconds
        if not applied:
            print(f"{C['y']}   ↳ lệnh không có thiết bị hợp lệ (maybom/denled/quatgio){C['r']}")
            return
        print(f"{C['g']}   ↳ ESP32 thực hiện: " + ", ".join(f"{DEVICE_VI[d]}={'BẬT' if s else 'TẮT'}" for d, s in applied.items()) + C['r'])
        self.publish_status()
        if self.a.manual_seconds > 0:
            ten = ", ".join(f"{DEVICE_VI[d]} {'BẬT' if v else 'TẮT'}" for d, v in applied.items())
            self.publish_ai_result(summary=f"Người dùng điều khiển tay: {ten}. Tạm dừng AI tự động {int(self.a.manual_seconds)}s.",
                                   reco={"action": "NONE", "message": "Đang ở chế độ MANUAL."})

    # ── vòng nền ──
    def _ticker(self):
        last_send = 0.0
        last_hb = time.time()
        while self.running:
            time.sleep(0.2)
            now = time.time()
            with self.lock:
                if self.mode == "MANUAL" and now >= self.manual_until:
                    self.mode = "AUTO"
                    back = True
                else:
                    back = False
            if back:
                print(f"{C['g']}[{ts()}] 🔁 Hết thời gian MANUAL -> trở về AUTO{C['r']}")
                self.publish_ai_result(summary="Hết thời gian điều khiển tay, AI tự động hoạt động lại.",
                                       reco={"action": "NONE", "message": "Đã quay về chế độ AUTO."})
            if self.auto_send and now - last_send >= self.interval:
                last_send = now
                self.send_current()
            if now - last_hb >= 30:                       # heartbeat trạng thái như ESP32 thật
                last_hb = now
                self.publish_status()

    def send_current(self, noise=None):
        noise = self.noise if noise is None else noise
        v = {k: self.target[k] + (random.gauss(0, NOISE[k]) if noise and self.target[k] > 0 else 0) for k in SENSORS}
        pl = self.publish_sensor(v)
        if self.edge_ai:
            self.edge_step({"nhiet_do": pl["nhiet_do"], "do_am": pl["do_am"], "anh_sang": pl["anh_sang"], "muc_nuoc": pl["muc_nuoc"]})
        return pl


# ════════════════════════════════════════════════════════════════════════════
# 5. BỘ KIỂM TRA TỰ ĐỘNG (AI Web + điều khiển)
# ════════════════════════════════════════════════════════════════════════════
def run_tests(esp, names, wait, progress=None):
    """Với mỗi kịch bản: reset thiết bị -> gửi cảm biến -> chờ AI Web trả lời -> đối chiếu lệnh điều khiển + engine."""
    saved_edge, esp.edge_ai = esp.edge_ai, False       # tắt AI Edge để chỉ quan sát phản ứng của AI Web
    saved_manual, esp.a.manual_seconds = esp.a.manual_seconds, 0
    saved_quiet, esp.a.quiet = esp.a.quiet, True
    rows = []
    # Kích hoạt công tắc AI Web trên MQTT để web_ai_subscriber phân tích
    esp.pub(TOPIC_AI_WEB_ENABLE, {"enabled": 1}, "BẬT AI WEB")
    time.sleep(0.3)
    print(f"\n{C['c']}══ KIỂM TRA TỰ ĐỘNG: {len(names)} kịch bản, chờ {wait}s/kịch bản (AI Edge tắt, chỉ quan sát AI Web) ══{C['r']}")
    for name in names:
        t, desc = SCENARIOS[name]
        v = vals_from_tuple(t)
        exp = analyze_l1(esp.cfg, v)
        with esp.lock:
            esp.state = {d: 0 for d in DEVICES}
            esp.mode = "AUTO"
        esp.publish_status()
        time.sleep(0.4)
        with esp.obs_lock:
            esp.observed.clear()
        t0 = time.time()
        esp.publish_sensor(v)
        time.sleep(wait)
        with esp.obs_lock:
            obs = [o for o in esp.observed if o[0] >= t0]
        ctrl, web_ai = {}, None
        for _, topic, pl in obs:
            try:
                d = json.loads(pl)
            except Exception:
                continue
            if topic == TOPIC_CONTROL:
                for k in DEVICES:
                    if k in d:
                        ctrl[k] = "ON" if int(d[k]) else "OFF"
            elif (topic.startswith("aquaponic/ai/result/") or topic.startswith("aquaponic/ai/web/result/")) and (
                str(d.get("ai_result", {}).get("engine", "")).startswith("web_") or "web" in topic
            ):
                web_ai = d
        exp_cmds = exp["commands"]
        engine = web_ai["ai_result"]["engine"] if web_ai else None
        exp_engine = "web_hybrid_v1" if exp["call_l2"] else "web_rule_v1"
        if web_ai is None:
            status = "KHÔNG PHẢN HỒI"
        elif not exp["abnormal"] and not ctrl:
            status = "PASS"
        elif ctrl == exp_cmds:
            status = "PASS"
        else:
            status = "SAI LỆNH"
        eng_note = "" if engine == exp_engine or web_ai is None else f" (engine={engine}, mong đợi {exp_engine}{' — service Python chưa chạy?' if exp_engine == 'web_hybrid_v1' else ''})"
        rows.append((name, status, exp_cmds, ctrl, engine, eng_note, web_ai))
        if progress:
            progress(rows[-1], len(rows), len(names))
        col = C["g"] if status == "PASS" else C["R"]
        print(f"{col}{status:<15}{C['r']} {name:<18} mong đợi lệnh {json.dumps(exp_cmds) if exp_cmds else '{}':<34} thực tế {json.dumps(ctrl) if ctrl else '{}'}{eng_note}")
        if web_ai:
            print(f"{C['d']}                  ↳ {web_ai.get('analysis', {}).get('summary', '')[:300]}{C['r']}")
    esp.edge_ai, esp.a.manual_seconds, esp.a.quiet = saved_edge, saved_manual, saved_quiet
    n_ok = sum(1 for r in rows if r[1] == "PASS")
    print(f"\n{C['c']}══ KẾT QUẢ: {n_ok}/{len(rows)} đạt ══{C['r']}")
    if any(r[1] == "KHÔNG PHẢN HỒI" for r in rows):
        print(f"{C['y']}Có kịch bản không thấy AI Web phản hồi: kiểm tra đã chạy  php AI_Web/web_ai_subscriber.php  và nó cùng broker/topic chưa.{C['r']}")
    return rows


# ════════════════════════════════════════════════════════════════════════════
# GIAO DIỆN (tkinter)
# ════════════════════════════════════════════════════════════════════════════
sim = sys.modules[__name__]      # phần GUI gọi  sim.xxx  -> chính là các hàm/lớp phía trên trong file này

import argparse
import queue
import tkinter as tk
from tkinter import messagebox, ttk
from types import SimpleNamespace


# ── màu sắc ──
BG = "#f1f5f9"
CARD = "#ffffff"
INK = "#0f172a"
MUTED = "#64748b"
LINE = "#e2e8f0"
GREEN, AMBER, RED, BLUE, GRAY, TEAL = "#16a34a", "#d97706", "#dc2626", "#2563eb", "#6b7280", "#0d9488"
LOG_BG, LOG_TX, LOG_RX, LOG_INFO = "#0b1220", "#67e8f9", "#fbbf24", "#94a3b8"
SEV_COL = {"normal": GREEN, "warning": AMBER, "danger": RED}
SEV_TXT = {"normal": "BÌNH THƯỜNG", "warning": "CẢNH BÁO", "danger": "NGUY HIỂM"}
LEVEL_COL = {"low": BLUE, "mid": GREEN, "high": AMBER, "fault": GRAY}
LEVEL_TXT = {"low": "THẤP", "mid": "VỪA", "high": "CAO", "fault": "LỖI"}

SENSOR_UI = [  # key, nhãn, đơn vị, min, max, bước
    ("nhiet_do", "Nhiệt độ", "°C", 15.0, 40.0, 0.1),
    ("do_am", "Độ ẩm", "%", 0.0, 100.0, 0.5),
    ("anh_sang", "Ánh sáng", "", 0.0, 1000.0, 5.0),
    ("muc_nuoc", "Mực nước", "", 0.0, 2200.0, 10.0),
]
FONT = ("Segoe UI", 10)
FONT_B = ("Segoe UI", 10, "bold")
FONT_H = ("Segoe UI", 11, "bold")
FONT_MONO = ("Consolas", 9)


def category(topic):
    if topic.endswith("sensor/data"):
        return "sensor"
    if topic.endswith("device/status"):
        return "status"
    if topic.endswith("/control"):
        return "control"
    return "ai"


class App:
    def __init__(self, root, opts):
        self.root = root
        self.opts = opts
        self.esp = None
        self.q = queue.Queue()
        self.testing = False
        self.cfg, self.cfg_src = sim.load_thresholds(opts.thresholds)

        root.title("Giả lập ESP32 — Aquaponic AI IoT")
        root.geometry("1280x880")
        root.minsize(1180, 780)
        root.configure(bg=BG)
        style = ttk.Style()
        try:
            style.theme_use("clam")
        except tk.TclError:
            pass
        style.configure("TFrame", background=BG)
        style.configure("TLabel", background=BG, foreground=INK, font=FONT)
        style.configure("TCheckbutton", background=CARD, foreground=INK, font=FONT)
        style.configure("Bar.TCheckbutton", background=BG)
        style.configure("TNotebook", background=BG)
        style.configure("TNotebook.Tab", font=FONT_B, padding=(14, 6))
        style.configure("Treeview", font=FONT, rowheight=24)
        style.configure("Treeview.Heading", font=FONT_B)
        style.configure("Go.TButton", font=FONT_B, padding=(12, 6))
        style.configure("TButton", font=FONT, padding=(10, 5))

        self._build_connection_bar()
        body = ttk.Frame(root)
        body.pack(fill="both", expand=True, padx=12, pady=(0, 8))
        body.columnconfigure(0, weight=5, uniform="c")
        body.columnconfigure(1, weight=4, uniform="c")
        body.columnconfigure(2, weight=5, uniform="c")
        body.rowconfigure(0, weight=0)
        body.rowconfigure(1, weight=1)
        self._build_sensor_panel(body)
        self._build_device_panel(body)
        self._build_ai_panel(body)
        self._build_bottom(body)

        self.status_var = tk.StringVar(value="Chưa kết nối. Nhấn “Kết nối”.")
        tk.Label(root, textvariable=self.status_var, anchor="w", bg=INK, fg="#e2e8f0", font=("Segoe UI", 9), padx=10, pady=3).pack(fill="x", side="bottom")

        self.update_edge_button()
        self.on_sensor_change()
        self.poll()
        root.protocol("WM_DELETE_WINDOW", self.on_close)
        if opts.autoconnect:
            root.after(300, self.connect)

    # ───────────────────────────── dựng giao diện ─────────────────────────────
    def card(self, parent, title, **grid):
        outer = tk.Frame(parent, bg=CARD, highlightbackground=LINE, highlightthickness=1)
        outer.grid(padx=5, pady=5, sticky="nsew", **grid)
        tk.Label(outer, text=title, bg=CARD, fg=INK, font=FONT_H, anchor="w").pack(fill="x", padx=12, pady=(10, 4))
        inner = tk.Frame(outer, bg=CARD)
        inner.pack(fill="both", expand=True, padx=12, pady=(0, 10))
        return inner

    def _build_connection_bar(self):
        o = self.opts
        bar = tk.Frame(self.root, bg=CARD, highlightbackground=LINE, highlightthickness=1)
        bar.pack(fill="x", padx=12, pady=10)
        self.v_host = tk.StringVar(value=o.host)
        self.v_port = tk.StringVar(value=str(o.port))
        self.v_user = tk.StringVar(value=o.user)
        self.v_pass = tk.StringVar(value=o.password)
        self.v_dev = tk.StringVar(value=str(o.device_id))
        self.v_tls = tk.BooleanVar(value=not o.no_tls)

        def field(label, var, w, show=None):
            tk.Label(bar, text=label, bg=CARD, fg=MUTED, font=("Segoe UI", 9)).pack(side="left", padx=(10, 3), pady=10)
            e = tk.Entry(bar, textvariable=var, width=w, font=FONT, relief="solid", bd=1, show=show or "")
            e.pack(side="left")
            return e

        field("Broker", self.v_host, 27)
        field("Cổng", self.v_port, 5)
        field("Tài khoản", self.v_user, 10)
        field("Mật khẩu", self.v_pass, 10, show="•")
        field("Thiết bị #", self.v_dev, 4)
        ttk.Checkbutton(bar, text="TLS", variable=self.v_tls).pack(side="left", padx=8)
        self.btn_conn = ttk.Button(bar, text="Kết nối", style="Go.TButton", width=13, command=self.toggle_connect)
        self.btn_conn.pack(side="left", padx=6)
        self.dot = tk.Label(bar, text="●  Chưa kết nối", bg=CARD, fg=GRAY, font=FONT_B)
        self.dot.pack(side="right", padx=14)

    def _build_sensor_panel(self, body):
        f = self.card(body, "📡  Cảm biến  →  aquaponic/sensor/data", row=0, column=0)
        self.v_sensor, self.v_fault, self.lbl_level = {}, {}, {}
        self.scales = {}
        for r, (key, label, unit, lo, hi, step) in enumerate(SENSOR_UI):
            tk.Label(f, text=label, bg=CARD, fg=INK, font=FONT_B, width=9, anchor="w").grid(row=r * 2, column=0, sticky="w", pady=(6, 0))
            var = tk.DoubleVar(value=sim.SCENARIOS["binh_thuong"][0][r])
            self.v_sensor[key] = var
            sc = tk.Scale(f, from_=lo, to=hi, resolution=step, orient="horizontal", variable=var, showvalue=0,
                          length=250, bg=CARD, highlightthickness=0, troughcolor=LINE, command=lambda _v: self.on_sensor_change())
            sc.grid(row=r * 2, column=1, sticky="ew", padx=6, pady=(6, 0))
            self.scales[key] = sc
            sp = tk.Spinbox(f, from_=lo, to=hi, increment=step, textvariable=var, width=7, font=FONT, relief="solid", bd=1,
                            command=self.on_sensor_change)
            sp.grid(row=r * 2, column=2, padx=(0, 4), pady=(6, 0))
            sp.bind("<KeyRelease>", lambda _e: self.on_sensor_change())
            tk.Label(f, text=unit, bg=CARD, fg=MUTED, width=2, anchor="w").grid(row=r * 2, column=3, pady=(6, 0))
            lv = tk.Label(f, text="VỪA", bg=GREEN, fg="white", font=("Segoe UI", 9, "bold"), width=6)
            lv.grid(row=r * 2, column=4, padx=4, pady=(6, 0))
            self.lbl_level[key] = lv
            fv = tk.BooleanVar(value=False)
            self.v_fault[key] = fv
            ttk.Checkbutton(f, text="Mô phỏng cảm biến lỗi (đọc 0)", variable=fv, command=self.on_sensor_change).grid(
                row=r * 2 + 1, column=1, columnspan=4, sticky="w", padx=6)
        f.columnconfigure(1, weight=1)

        sep = tk.Frame(f, bg=LINE, height=1)
        sep.grid(row=8, column=0, columnspan=5, sticky="ew", pady=8)
        tk.Label(f, text="Kịch bản", bg=CARD, fg=INK, font=FONT_B).grid(row=9, column=0, sticky="w")
        self.v_scn = tk.StringVar(value="binh_thuong")
        cb = ttk.Combobox(f, textvariable=self.v_scn, values=list(sim.SCENARIOS), state="readonly", width=22, font=FONT)
        cb.grid(row=9, column=1, sticky="w", padx=6)
        cb.bind("<<ComboboxSelected>>", lambda _e: self.apply_scenario(False))
        ttk.Button(f, text="Áp dụng + gửi", command=lambda: self.apply_scenario(True)).grid(row=9, column=2, columnspan=3, sticky="w")
        self.lbl_scn = tk.Label(f, text=sim.SCENARIOS["binh_thuong"][1], bg=CARD, fg=MUTED, font=("Segoe UI", 9), anchor="w", justify="left", wraplength=430)
        self.lbl_scn.grid(row=10, column=0, columnspan=5, sticky="w", pady=(2, 6))

        row = tk.Frame(f, bg=CARD)
        row.grid(row=11, column=0, columnspan=5, sticky="w")
        ttk.Button(row, text="📤  Gửi 1 gói ngay", style="Go.TButton", command=self.send_once).pack(side="left")
        self.v_auto = tk.BooleanVar(value=False)
        ttk.Checkbutton(row, text="Tự gửi mỗi", variable=self.v_auto, command=self.on_auto).pack(side="left", padx=(14, 2))
        self.v_int = tk.StringVar(value="5")
        sp = tk.Spinbox(row, from_=1, to=120, width=4, textvariable=self.v_int, font=FONT, relief="solid", bd=1, command=self.on_auto)
        sp.pack(side="left")
        sp.bind("<KeyRelease>", lambda _e: self.on_auto())
        tk.Label(row, text="giây", bg=CARD, fg=MUTED).pack(side="left", padx=(3, 10))
        self.v_noise = tk.BooleanVar(value=True)
        ttk.Checkbutton(f, text="Thêm nhiễu ngẫu nhiên nhỏ khi tự gửi (giống cảm biến thật)", variable=self.v_noise, command=self.on_auto).grid(
            row=12, column=0, columnspan=5, sticky="w", pady=(6, 0))

    def _build_device_panel(self, body):
        f = self.card(body, "⚙️  Thiết bị trên ESP32", row=0, column=1)
        tk.Label(f, text="Bấm = “nút vật lý”: đổi trạng thái rồi báo lên web (aquaponic/device/status)", bg=CARD, fg=MUTED,
                 font=("Segoe UI", 9), wraplength=320, justify="left", anchor="w").pack(fill="x")
        self.dev_btn = {}
        for dev in sim.DEVICES:
            b = tk.Button(f, text="", font=("Segoe UI", 12, "bold"), relief="flat", bd=0, pady=9, cursor="hand2",
                          command=lambda d=dev: self.toggle_device(d))
            b.pack(fill="x", pady=4)
            self.dev_btn[dev] = b

        row = tk.Frame(f, bg=CARD)
        row.pack(fill="x", pady=(8, 2))
        self.lbl_mode = tk.Label(row, text="Chế độ: AUTO", bg=GREEN, fg="white", font=FONT_B, padx=10, pady=3)
        self.lbl_mode.pack(side="left")
        rowb = tk.Frame(f, bg=CARD)
        rowb.pack(fill="x", pady=(2, 2))
        ttk.Button(rowb, text="→ AUTO", command=lambda: self.set_mode("AUTO")).pack(side="left", expand=True, fill="x", padx=(0, 3))
        ttk.Button(rowb, text="→ MANUAL", command=lambda: self.set_mode("MANUAL")).pack(side="left", expand=True, fill="x", padx=(3, 0))
        row2 = tk.Frame(f, bg=CARD)
        row2.pack(fill="x", pady=2)
        tk.Label(row2, text="Sau mỗi lệnh, giữ MANUAL", bg=CARD, fg=MUTED).pack(side="left")
        self.v_manual = tk.StringVar(value=str(int(self.opts.manual_seconds)))
        sp = tk.Spinbox(row2, from_=0, to=600, width=4, textvariable=self.v_manual, font=FONT, relief="solid", bd=1, command=self.on_manual_secs)
        sp.pack(side="left", padx=4)
        sp.bind("<KeyRelease>", lambda _e: self.on_manual_secs())
        tk.Label(row2, text="s (0 = tắt)", bg=CARD, fg=MUTED, font=("Segoe UI", 9)).pack(side="left")
        self.v_edge = tk.BooleanVar(value=self.opts.edge_ai)
        ttk.Checkbutton(f, text="AI IoT (Edge) tự điều khiển + báo kết quả", variable=self.v_edge, command=self.on_edge).pack(anchor="w", pady=(6, 0))

        tk.Frame(f, bg=LINE, height=1).pack(fill="x", pady=8)
        tk.Label(f, text="🖱  Giả lập nút Dashboard  (aquaponic/control)", bg=CARD, fg=INK, font=FONT_B, wraplength=340, justify="left").pack(anchor="w")
        for dev in sim.DEVICES:
            r = tk.Frame(f, bg=CARD)
            r.pack(fill="x", pady=2)
            tk.Label(r, text=sim.DEVICE_VI[dev], bg=CARD, fg=INK, width=10, anchor="w").pack(side="left")
            ttk.Button(r, text="BẬT", width=7, command=lambda d=dev: self.dashboard_cmd(d, 1)).pack(side="left", padx=3)
            ttk.Button(r, text="TẮT", width=7, command=lambda d=dev: self.dashboard_cmd(d, 0)).pack(side="left", padx=3)

    def _ai_card(self, parent, title, row):
        f = self.card(parent, title, row=row, column=0)
        sev = tk.Label(f, text="—", bg=GRAY, fg="white", font=FONT_B, padx=10, pady=3)
        sev.pack(anchor="w")
        eng = tk.Label(f, text="Chưa có kết quả", bg=CARD, fg=MUTED, font=("Segoe UI", 9), anchor="w", justify="left", wraplength=380)
        eng.pack(fill="x", pady=(4, 2))
        summ = tk.Label(f, text="", bg=CARD, fg=INK, font=FONT, anchor="w", justify="left", wraplength=400)
        summ.pack(fill="x", pady=2)
        reco = tk.Label(f, text="", bg=CARD, fg=TEAL, font=FONT, anchor="w", justify="left", wraplength=400)
        reco.pack(fill="x", pady=2)
        act = tk.Label(f, text="", bg=CARD, fg=BLUE, font=FONT_B, anchor="w", justify="left", wraplength=400)
        act.pack(fill="x", pady=2)
        return {"sev": sev, "eng": eng, "summ": summ, "reco": reco, "act": act}

    def _build_ai_panel(self, body):
        holder = tk.Frame(body, bg=BG)
        holder.grid(row=0, column=2, sticky="nsew")
        holder.columnconfigure(0, weight=1)
        holder.rowconfigure(0, weight=1)
        holder.rowconfigure(1, weight=1)
        self.ai_edge = self._ai_card(holder, "🤖  AI Edge (ESP32 giả lập)", 0)
        edge_outer = self.ai_edge["sev"].master.master
        self.btn_edge = tk.Button(edge_outer, text="", font=("Segoe UI", 10, "bold"), relief="flat", bd=0, padx=12, pady=4,
                                  cursor="hand2", command=self.toggle_edge)
        self.btn_edge.place(relx=1.0, x=-10, y=8, anchor="ne")
        self.ai_web = self._ai_card(holder, "🌐  AI Web (server — hybrid)", 1)

    def _build_bottom(self, body):
        nb = ttk.Notebook(body)
        nb.grid(row=1, column=0, columnspan=3, sticky="nsew", padx=5, pady=(2, 4))
        # --- Nhật ký ---
        log_tab = ttk.Frame(nb)
        nb.add(log_tab, text="  Nhật ký MQTT  ")
        top = ttk.Frame(log_tab)
        top.pack(fill="x", pady=(6, 2))
        self.v_show = {}
        for k, t in [("sensor", "Cảm biến"), ("status", "Trạng thái"), ("control", "Điều khiển"), ("ai", "AI")]:
            self.v_show[k] = tk.BooleanVar(value=True)
            ttk.Checkbutton(top, text=t, variable=self.v_show[k], style="Bar.TCheckbutton").pack(side="left", padx=6)
        ttk.Button(top, text="Xóa nhật ký", command=lambda: (self.log.config(state="normal"), self.log.delete("1.0", "end"), self.log.config(state="disabled"))).pack(side="left", padx=10)
        self.v_rtopic = tk.StringVar(value="aquaponic/")
        self.v_rjson = tk.StringVar(value='{"quatgio":1}')
        ttk.Button(top, text="Gửi tuỳ ý", command=self.send_raw).pack(side="right", padx=4)
        tk.Entry(top, textvariable=self.v_rjson, width=34, font=FONT_MONO, relief="solid", bd=1).pack(side="right", padx=3)
        tk.Entry(top, textvariable=self.v_rtopic, width=26, font=FONT_MONO, relief="solid", bd=1).pack(side="right", padx=3)
        wrap = tk.Frame(log_tab, bg=LOG_BG)
        wrap.pack(fill="both", expand=True)
        self.log = tk.Text(wrap, bg=LOG_BG, fg="#e2e8f0", font=FONT_MONO, wrap="none", state="disabled", relief="flat", height=10)
        sb = ttk.Scrollbar(wrap, command=self.log.yview)
        self.log.config(yscrollcommand=sb.set)
        sb.pack(side="right", fill="y")
        self.log.pack(fill="both", expand=True)
        self.log.tag_config("tx", foreground=LOG_TX)
        self.log.tag_config("rx", foreground=LOG_RX)
        self.log.tag_config("info", foreground=LOG_INFO)
        # --- Kiểm tra ---
        test_tab = ttk.Frame(nb)
        nb.add(test_tab, text="  Kiểm tra tự động AI Web  ")
        bar = ttk.Frame(test_tab)
        bar.pack(fill="x", pady=6)
        self.btn_test = ttk.Button(bar, text="▶  Chạy tất cả kịch bản", style="Go.TButton", command=self.run_tests)
        self.btn_test.pack(side="left", padx=6)
        tk.Label(bar, text="Chờ", bg=BG, fg=MUTED).pack(side="left", padx=(12, 2))
        self.v_wait = tk.StringVar(value="3")
        tk.Spinbox(bar, from_=1, to=30, width=4, textvariable=self.v_wait, font=FONT, relief="solid", bd=1).pack(side="left")
        tk.Label(bar, text="giây / kịch bản (tạm tắt AI Edge để chỉ quan sát AI Web)", bg=BG, fg=MUTED).pack(side="left", padx=4)
        self.lbl_test = tk.Label(bar, text="", bg=BG, fg=INK, font=FONT_B)
        self.lbl_test.pack(side="right", padx=10)
        cols = ("name", "result", "expect", "actual", "engine")
        self.tree = ttk.Treeview(test_tab, columns=cols, show="headings", height=8)
        for c, t, w in [("name", "Kịch bản", 150), ("result", "Kết quả", 130), ("expect", "Lệnh mong đợi", 260), ("actual", "Lệnh thực tế (AI Web gửi)", 260), ("engine", "Engine", 130)]:
            self.tree.heading(c, text=t)
            self.tree.column(c, width=w, anchor="w")
        self.tree.tag_configure("ok", foreground=GREEN)
        self.tree.tag_configure("bad", foreground=RED)
        ts = ttk.Scrollbar(test_tab, command=self.tree.yview)
        self.tree.config(yscrollcommand=ts.set)
        ts.pack(side="right", fill="y")
        self.tree.pack(fill="both", expand=True)

    # ───────────────────────────── cảm biến ─────────────────────────────
    def effective_values(self):
        out = {}
        for k, *_ in SENSOR_UI:
            try:
                v = float(self.v_sensor[k].get())
            except (tk.TclError, ValueError):
                v = 0.0
            out[k] = 0.0 if self.v_fault[k].get() else v
        return out

    def on_sensor_change(self, *_):
        vals = self.effective_values()
        for k, *_r in SENSOR_UI:
            lv = sim.get_level(self.cfg, k, vals[k])
            self.lbl_level[k].config(text=LEVEL_TXT[lv], bg=LEVEL_COL[lv])
            self.scales[k].config(state="disabled" if self.v_fault[k].get() else "normal")
        if self.esp:
            self.esp.target = dict(vals)

    def apply_scenario(self, send):
        name = self.v_scn.get()
        t, desc = sim.SCENARIOS[name]
        self.lbl_scn.config(text=desc)
        for (k, *_r), val in zip(SENSOR_UI, t):
            fault = val <= 0
            self.v_fault[k].set(fault)
            if not fault:
                self.v_sensor[k].set(val)
        self.on_sensor_change()
        if send:
            self.send_once()

    def need_conn(self):
        if not self.esp:
            self.status("⚠ Chưa kết nối broker — nhấn “Kết nối” trước.")
            return False
        return True

    def send_once(self):
        if not self.need_conn():
            return
        self.esp.target = self.effective_values()
        pl = self.esp.send_current(noise=False)
        self.status(f"Đã gửi cảm biến: {pl}")

    def on_auto(self):
        if not self.esp:
            return
        try:
            self.esp.interval = max(0.5, float(self.v_int.get()))
        except ValueError:
            pass
        self.esp.noise = self.v_noise.get()
        self.esp.target = self.effective_values()
        self.esp.auto_send = self.v_auto.get()
        self.status(f"Tự gửi cảm biến: {'BẬT mỗi %gs' % self.esp.interval if self.esp.auto_send else 'TẮT'}")

    # ───────────────────────────── thiết bị ─────────────────────────────
    def toggle_device(self, dev):
        if not self.need_conn():
            return
        with self.esp.lock:
            self.esp.state[dev] = 0 if self.esp.state[dev] else 1
        self.esp.publish_status()

    def dashboard_cmd(self, dev, val):
        if not self.need_conn():
            return
        self.esp.pub(sim.TOPIC_CONTROL, {dev: val}, "LỆNH TỪ DASHBOARD (giả lập)")
        self.root.after(400, lambda: self.esp.handle_control(json.dumps({dev: val})))

    def set_mode(self, mode):
        if not self.need_conn():
            return
        secs = self._manual_secs() or 30
        with self.esp.lock:
            self.esp.mode = mode
            self.esp.manual_until = time.time() + secs if mode == "MANUAL" else 0
        self.esp.publish_ai_result(reco={"action": "NONE", "message": f"Chế độ {mode}"})

    def _manual_secs(self):
        try:
            return max(0.0, float(self.v_manual.get()))
        except ValueError:
            return 0.0

    def on_manual_secs(self):
        if self.esp:
            self.esp.a.manual_seconds = self._manual_secs()

    def toggle_edge(self):
        self.v_edge.set(not self.v_edge.get())
        self.on_edge()

    def on_edge(self):
        on = self.v_edge.get()
        if self.esp:
            self.esp.edge_ai = on
        self.update_edge_button()
        if not on:
            c = self.ai_edge
            c["sev"].config(text="ĐÃ TẮT", bg=GRAY)
            c["eng"].config(text="AI IoT (Edge) đang tắt — ESP32 không tự điều khiển, không gửi kết quả AI. Chỉ AI Web hoạt động.")
            c["summ"].config(text="")
            c["reco"].config(text="")
            c["act"].config(text="")
        self.status("AI IoT (Edge): " + ("BẬT" if on else "TẮT — đang test riêng AI Web"))

    def update_edge_button(self):
        on = self.v_edge.get()
        self.btn_edge.config(text="🤖 AI IoT: BẬT" if on else "⛔ AI IoT: TẮT", bg=GREEN if on else RED, fg="white",
                             activebackground="#15803d" if on else "#b91c1c", activeforeground="white")

    def send_raw(self):
        if not self.need_conn():
            return
        self.esp.pub(self.v_rtopic.get().strip(), self.v_rjson.get().strip(), "RAW")

    # ───────────────────────────── kết nối ─────────────────────────────
    def toggle_connect(self):
        if self.esp:
            self.disconnect()
        else:
            self.connect()

    def connect(self):
        if self.esp or getattr(self, "_connecting", False):
            return
        try:
            port = int(self.v_port.get())
        except ValueError:
            messagebox.showerror("Lỗi", "Cổng phải là số.")
            return
        a = SimpleNamespace(host=self.v_host.get().strip(), port=port, user=self.v_user.get(), password=self.v_pass.get(),
                            no_tls=not self.v_tls.get(), insecure=False, device_id=self.v_dev.get().strip() or "18",
                            thresholds=self.opts.thresholds, edge_ai=self.v_edge.get(), interval=5.0,
                            manual_seconds=self._manual_secs(), quiet=True, wait=3.0)
        self._connecting = True
        self.btn_conn.config(state="disabled")
        self.dot.config(text="●  Đang kết nối…", fg=AMBER)

        def work():
            try:
                esp = sim.ESP32(a)
                esp.on_event = lambda kind, topic, payload: self.q.put(("mqtt", kind, topic, payload))
                esp.start()
                esp.publish_status()
                self.q.put(("connected", esp))
            except Exception as e:  # noqa: BLE001
                self.q.put(("conn_error", str(e)))

        threading.Thread(target=work, daemon=True).start()

    def disconnect(self):
        esp, self.esp = self.esp, None
        if esp:
            esp.running = False
            try:
                esp.cli.loop_stop()
                esp.cli.disconnect()
            except Exception:  # noqa: BLE001
                pass
        self.btn_conn.config(text="Kết nối", state="normal")
        self.dot.config(text="●  Chưa kết nối", fg=GRAY)
        self.status("Đã ngắt kết nối.")

    # ───────────────────────────── kiểm tra tự động ─────────────────────────────
    def run_tests(self):
        if not self.need_conn() or self.testing:
            return
        try:
            wait = max(1.0, float(self.v_wait.get()))
        except ValueError:
            wait = 3.0
        self.testing = True
        self.btn_test.config(state="disabled")
        for i in self.tree.get_children():
            self.tree.delete(i)
        self.lbl_test.config(text="Đang chạy…", fg=AMBER)
        esp = self.esp

        def work():
            try:
                rows = sim.run_tests(esp, list(sim.SCENARIOS), wait, progress=lambda r, i, n: self.q.put(("test_row", r, i, n)))
                self.q.put(("test_done", rows))
            except Exception as e:  # noqa: BLE001
                self.q.put(("test_err", str(e)))

        threading.Thread(target=work, daemon=True).start()

    # ───────────────────────────── vòng cập nhật giao diện ─────────────────────────────
    def status(self, text):
        self.status_var.set(text)

    def poll(self):
        try:
            while True:
                self.handle(self.q.get_nowait())
        except queue.Empty:
            pass
        self.refresh()
        self.root.after(150, self.poll)

    def handle(self, item):
        kind = item[0]
        if kind == "connected":
            self.esp = item[1]
            self._connecting = False
            self.btn_conn.config(text="Ngắt kết nối", state="normal")
            self.status(f"Đã kết nối {self.esp.a.host}:{self.esp.a.port} — thiết bị #{self.esp.device_id} — ngưỡng: {self.esp.cfg_src}")
            self.on_sensor_change()
            self.on_auto()
        elif kind == "conn_error":
            self._connecting = False
            self.btn_conn.config(state="normal")
            self.dot.config(text="●  Chưa kết nối", fg=GRAY)
            self.status("Kết nối thất bại.")
            messagebox.showerror("Không kết nối được", item[1])
        elif kind == "mqtt":
            self.on_mqtt(*item[1:])
        elif kind == "test_row":
            (name, status, exp, ctrl, engine, note, _ai), i, n = item[1:]
            self.tree.insert("", "end", values=(name, status, json.dumps(exp) if exp else "{}", json.dumps(ctrl) if ctrl else "{}", (engine or "—") + (" ⚠" if note else "")),
                             tags=("ok" if status == "PASS" else "bad",))
            self.tree.yview_moveto(1)
            self.lbl_test.config(text=f"{i}/{n}", fg=AMBER)
        elif kind == "test_done":
            rows = item[1]
            ok = sum(1 for r in rows if r[1] == "PASS")
            self.testing = False
            self.btn_test.config(state="normal")
            self.lbl_test.config(text=f"{ok}/{len(rows)} ĐẠT", fg=GREEN if ok == len(rows) else RED)
            if any(r[1] == "KHÔNG PHẢN HỒI" for r in rows):
                self.status("Có kịch bản AI Web không phản hồi — kiểm tra đã chạy web_ai_subscriber.php chưa.")
        elif kind == "test_err":
            self.testing = False
            self.btn_test.config(state="normal")
            self.lbl_test.config(text="Lỗi", fg=RED)
            messagebox.showerror("Lỗi kiểm tra", item[1])

    def on_mqtt(self, direction, topic, payload):
        cat = category(topic)
        if self.v_show[cat].get():
            self.log.config(state="normal")
            arrow = "▲ GỬI " if direction == "tx" else "▼ NHẬN"
            self.log.insert("end", f"[{time.strftime('%H:%M:%S')}] {arrow} {topic}\n", direction)
            self.log.insert("end", f"            {payload[:700]}\n", "info")
            if int(self.log.index("end-1c").split(".")[0]) > 1600:
                self.log.delete("1.0", "400.0")
            self.log.see("end")
            self.log.config(state="disabled")
        if topic.startswith("aquaponic/ai/result/") or topic.startswith("aquaponic/ai/web/result/"):
            try:
                d = json.loads(payload)
            except json.JSONDecodeError:
                return
            engine = str(d.get("ai_result", {}).get("engine", ""))
            if engine.startswith("web_") or "web" in topic:
                self.show_ai(self.ai_web, d)
            elif engine:
                self.show_ai(self.ai_edge, d)

    def show_ai(self, card, d):
        ar, an, rec, act = d.get("ai_result", {}), d.get("analysis", {}), d.get("recommendation", {}), d.get("action_taken")
        sev = ar.get("severity", "normal")
        card["sev"].config(text=SEV_TXT.get(sev, sev), bg=SEV_COL.get(sev, GRAY))
        t = d.get("timestamp", "")[11:19]
        card["eng"].config(text=f"engine: {ar.get('engine')}   ·   mode: {d.get('mode')}   ·   loại: {ar.get('anomaly_type')}   ·   {t}")
        card["summ"].config(text=(an.get("summary") or "")[:700])
        msg = rec.get("message", "")
        card["reco"].config(text=("💡 " + msg) if msg and rec.get("action") != "NONE" else "")
        card["act"].config(text=(f"⚡ AI {'BẬT' if act.get('command') == 'ON' else 'TẮT'} {sim.DEVICE_VI.get(act.get('device'), act.get('device'))} — {act.get('reason', '')}") if act else "")

    def refresh(self):
        esp = self.esp
        for dev, b in self.dev_btn.items():
            on = bool(esp.state[dev]) if esp else False
            b.config(text=f"{sim.DEVICE_VI[dev]}:  {'BẬT' if on else 'TẮT'}", bg=GREEN if on else "#cbd5e1", fg="white" if on else INK,
                     activebackground=GREEN if on else "#b6c2d1")
        if esp:
            with esp.lock:
                manual = esp.mode == "MANUAL"
                rem = max(0, int(esp.manual_until - time.time()))
            self.lbl_mode.config(text=f"Chế độ: MANUAL ({rem}s)" if manual else "Chế độ: AUTO", bg=AMBER if manual else GREEN)
            if esp.edge_ai != self.v_edge.get():
                self.v_edge.set(esp.edge_ai)
            self.update_edge_button()
            ok = esp.cli.is_connected()
            self.dot.config(text="●  Đã kết nối" if ok else "●  Mất kết nối…", fg=GREEN if ok else AMBER)
        else:
            self.lbl_mode.config(text="Chế độ: —", bg=GRAY)

    def on_close(self):
        if self.esp:
            self.disconnect()
        self.root.destroy()


def main():
    ap = argparse.ArgumentParser(description="Giao diện giả lập ESP32")
    ap.add_argument("--host", default=os.environ.get("MQTT_HOST", "56865ed923c247919013bdef6430f5c6.s1.eu.hivemq.cloud"))
    ap.add_argument("--port", type=int, default=int(os.environ.get("MQTT_PORT", "8883")))
    ap.add_argument("--user", default=os.environ.get("MQTT_USER", "esp32_user"))
    ap.add_argument("--password", default=os.environ.get("MQTT_PASS", "123456Esp32@"))
    ap.add_argument("--no-tls", action="store_true")
    ap.add_argument("--device-id", default=os.environ.get("DEVICE_ID", "18"))
    ap.add_argument("--thresholds")
    ap.add_argument("--manual-seconds", type=float, default=30.0)
    ap.add_argument("--no-edge-ai", dest="edge_ai", action="store_false")
    ap.add_argument("--autoconnect", action="store_true")
    opts = ap.parse_args()
    root = tk.Tk()
    App(root, opts)
    root.mainloop()


if __name__ == "__main__":
    main()