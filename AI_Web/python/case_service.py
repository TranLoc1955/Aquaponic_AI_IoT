"""
case_service.py — LỚP 2 của AI Web: tra LUẬT học từ dữ liệu (model/rules.json)
để phân tích tình huống mà ngưỡng mặc định (engine.php) không giải thích được.

  python case_service.py            # lắng nghe 127.0.0.1:5001
  POST /analyze  {"nhiet_do":..,"do_am":..,"anh_sang":..,"muc_nuoc":..}
  GET  /health

Chỉ dùng thư viện chuẩn. Service CHỈ TRẢ PHÂN TÍCH (đọc-chỉ); việc điều khiển thiết bị
do lớp 1 (PHP) quyết định.
"""
import json, os, sys, time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

import core

HOST = os.environ.get("CASE_HOST", "127.0.0.1")
PORT = int(os.environ.get("CASE_PORT", "5001"))

CFG = core.load_json(core.THRESHOLDS_PATH)
RULES = core.load_json(core.RULES_PATH)
FRAG_META = RULES["fragments"]
SUPPORT = RULES["combo_support"]


def analyze(values):
    t0 = time.perf_counter()
    for s in core.SENSORS:
        if values.get(s) is None or values.get(s) == "":
            raise ValueError(f"Thiếu giá trị cảm biến '{s}'")
    levels = {s: core.get_level(CFG, s, values[s]) for s in core.SENSORS}
    fragments, commands, matched = core.apply_rules(RULES, levels)

    near = core.near_thresholds(CFG, values)
    support = SUPPORT.get(core.combo_key(levels), 0)
    if support < 50:
        conf = "thap"
    elif near:
        conf = "trung_binh"
    else:
        conf = "cao"

    ket_hop = [f for f in fragments if FRAG_META[f]["kind"] == "ket_hop"]
    label = "; ".join(FRAG_META[f]["vi"] for f in fragments) if fragments else "Bình thường: không cần bật thiết bị"
    n_abn = sum(1 for l in levels.values() if l != "mid")
    severity = "normal" if n_abn == 0 else "warning"

    return {
        "status": "success",
        "mode": "advise",                       # luôn chỉ gợi ý/hiển thị
        "label": label,
        "fragments": [{"id": f, "vi": FRAG_META[f]["vi"], "kind": FRAG_META[f]["kind"]} for f in fragments],
        "ket_hop": ket_hop,                     # tình huống kết hợp nhiều cảm biến
        "commands": commands,                   # lệnh KHUYẾN NGHỊ (0/1) cho từng thiết bị
        "levels": levels,
        "rules": [{"id": r["id"], "text": r["text"]} for r in matched],
        "confidence": {"muc": conf, "ho_tro": support, "sat_nguong": near},
        "severity": severity,
        "nguon": RULES["nguon"],
        "latency_ms": round((time.perf_counter() - t0) * 1000, 3),
    }


class Handler(BaseHTTPRequestHandler):
    def _send(self, code, obj):
        body = json.dumps(obj, ensure_ascii=False).encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self):
        if self.path == "/health":
            self._send(200, {"status": "ok", "so_luat": len(RULES["fragment_rules"]) + len(RULES["device_rules"]),
                             "nguon": RULES["nguon"], "so_dong": RULES["so_dong"]})
        else:
            self._send(404, {"status": "error", "message": "not found"})

    def do_POST(self):
        if self.path != "/analyze":
            return self._send(404, {"status": "error", "message": "not found"})
        try:
            n = int(self.headers.get("Content-Length", 0))
            values = json.loads(self.rfile.read(n) or b"{}")
            self._send(200, analyze(values))
        except (ValueError, TypeError, json.JSONDecodeError) as e:
            self._send(400, {"status": "error", "message": str(e)})

    def log_message(self, *a):  # im lặng, tránh spam console
        pass


if __name__ == "__main__":
    srv = ThreadingHTTPServer((HOST, PORT), Handler)
    print(f"[case_service] {len(RULES['fragment_rules'])+len(RULES['device_rules'])} luật từ {RULES['nguon']} "
          f"({RULES['so_dong']} dòng) — http://{HOST}:{PORT}/analyze")
    try:
        srv.serve_forever()
    except KeyboardInterrupt:
        pass
