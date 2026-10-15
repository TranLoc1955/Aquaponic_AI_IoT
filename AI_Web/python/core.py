"""
core.py — logic dùng chung cho train_rules.py, case_service.py (và kiểm tra).
Chỉ dùng thư viện chuẩn (service chạy không cần pandas/sklearn).
"""
import json
import os

BASE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))  # thư mục AI_Web
THRESHOLDS_PATH = os.path.join(BASE, "thresholds.json")
RULES_PATH = os.path.join(BASE, "model", "rules.json")

SENSORS = ["nhiet_do", "do_am", "anh_sang", "muc_nuoc"]
LEVELS = ["low", "mid", "high", "fault"]
DEVICES = ["quatgio", "denled", "maybom"]
LEVEL_VI = {"low": "Thấp", "mid": "Vừa", "high": "Cao", "fault": "Lỗi"}


def load_json(path):
    with open(path, encoding="utf-8") as f:
        return json.load(f)


def get_level(cfg, sensor, value):
    """Quy giá trị đo về mức: low | mid | high | fault (theo thresholds.json)."""
    s = cfg["sensors"][sensor]
    v = float(value)
    if v <= s["fault_at_or_below"]:
        return "fault"
    idx = sum(1 for b in s["breaks"] if v >= b)  # 0,1,2
    return ["low", "mid", "high"][idx]


def near_thresholds(cfg, values):
    """Cảm biến nào đang nằm sát mốc ngưỡng (trong near_margin)."""
    out = []
    for sensor in SENSORS:
        s = cfg["sensors"][sensor]
        v = float(values[sensor])
        if v <= s["fault_at_or_below"]:
            continue
        if any(abs(v - b) <= s["near_margin"] for b in s["breaks"]):
            out.append(sensor)
    return out


def cond_match(cond, levels):
    lv = levels[cond["sensor"]]
    if "is" in cond:
        return lv == cond["is"]
    return lv not in cond["not"]


def rule_match(rule, levels):
    return all(cond_match(c, levels) for c in rule["if"])


def apply_rules(rules, levels):
    """Trả về (danh sách tình huống khớp, lệnh thiết bị {dev:0|1}, luật đã khớp)."""
    fragments, matched = [], []
    for r in rules["fragment_rules"]:
        if rule_match(r, levels):
            matched.append(r)
            if r["target"] not in fragments:
                fragments.append(r["target"])
    commands = {d: 0 for d in DEVICES}
    for r in rules["device_rules"]:
        if rule_match(r, levels):
            commands[r["target"]] = 1
            matched.append(r)
    return fragments, commands, matched


def combo_key(levels):
    return "|".join(levels[s] for s in SENSORS)
