"""
train_rules.py — HỌC LUẬT từ dữ liệu cảm biến (CSV) và xuất ra model/rules.json.

  pip install pandas scikit-learn
  python train_rules.py [đường_dẫn_csv]      # mặc định ../data/sensor_data_train.csv

Cách làm:
  1. Với mỗi dòng, quy giá trị đo về mức (low/mid/high/fault) bằng thresholds.json
     (đúng bộ ngưỡng lớp 1 đang dùng -> 2 lớp không lệch nhau).
  2. Với mỗi "tình huống" (cột huong_xu_ly tách theo ';') và mỗi lệnh thiết bị
     (quat, den_led, may_bom), huấn luyện 1 cây quyết định trên các mức đó,
     rồi trích đường đi về lá dương thành luật IF-THEN đọc được.
  3. Đánh giá: học trên ngày 1-5, kiểm tra trên ngày 6-7 (tách theo thời gian);
     sau đó học lại trên toàn bộ dữ liệu để xuất rules.json.
"""
import sys, os, json, collections
import pandas as pd
from sklearn.tree import DecisionTreeClassifier

import core

CSV_DEFAULT = os.path.join(core.BASE, "data", "sensor_data_train.csv")
COLMAP = {"temperature": "nhiet_do", "humidity": "do_am", "light": "anh_sang", "water_level": "muc_nuoc"}
DEVCOL = {"quat": "quatgio", "den_led": "denled", "may_bom": "maybom"}

# khoá tình huống (đầu mỗi mảnh trong huong_xu_ly, bỏ dấu) -> id, tiếng Việt, loại
FRAGMENTS = {
    "Nhiet cao":            ("nhiet_cao",     "Nhiệt cao: bật quạt",                              "don_le"),
    "Do am cao":            ("do_am_cao",     "Độ ẩm cao: bật quạt thông gió",                    "don_le"),
    "Thieu sang":           ("thieu_sang",    "Thiếu sáng: bật đèn LED",                          "don_le"),
    "Muc nuoc thap":        ("muc_nuoc_thap", "Mực nước thấp: bật bơm",                           "don_le"),
    "Nuoc day":             ("nuoc_day",      "Nước đầy: không bơm",                              "don_le"),
    "Cam bien nhiet loi":   ("loi_nhiet",     "Cảm biến nhiệt lỗi: bật quạt an toàn",             "loi"),
    "Cam bien sang loi":    ("loi_sang",      "Cảm biến sáng lỗi: tắt đèn",                       "loi"),
    "Cam bien do am loi":   ("loi_do_am",     "Cảm biến độ ẩm lỗi: bỏ qua độ ẩm",                 "loi"),
    "Cam bien nuoc loi":    ("loi_nuoc",      "Cảm biến nước lỗi: tắt bơm",                       "loi"),
    "Nang gat":             ("nang_gat",      "Nắng gắt: bật quạt hạ nhiệt",   "ket_hop"),
    "Nong + kho":           ("nong_kho",      "Nóng + khô: bật bơm tưới hạ nhiệt",                "ket_hop"),
    "Nong + toi":           ("nong_toi",      "Nóng + tối: không bật đèn (tránh sinh nhiệt)",     "ket_hop"),
    "Mat + am":             ("mat_am",        "Mát + ẩm: bật đèn LED để ấm và khô",               "ket_hop"),
}


def feature_names():
    return [(s, l) for s in core.SENSORS for l in core.LEVELS]


def to_matrix(levels_df):
    cols = {}
    for s, l in feature_names():
        cols[f"{s}={l}"] = (levels_df[s] == l).astype(int)
    return pd.DataFrame(cols)


def extract_rules(tree, names, target, prefix):
    """Trích mọi đường đi tới lá dương thành luật; gộp điều kiện theo từng cảm biến."""
    t = tree.tree_
    pos = list(tree.classes_).index(1) if 1 in tree.classes_ else None
    rules = []
    if pos is None:
        return rules

    def walk(node, path):
        if t.children_left[node] == -1:
            if t.value[node][0].argmax() == pos:
                rules.append(list(path))
            return
        feat = names[t.feature[node]]
        walk(t.children_left[node], path + [(feat, 0)])   # feature <= 0.5  -> không phải mức này
        walk(t.children_right[node], path + [(feat, 1)])  # feature > 0.5   -> đúng mức này

    walk(0, [])
    out = []
    for i, path in enumerate(rules, 1):
        per = collections.defaultdict(lambda: {"is": None, "not": []})
        for feat, val in path:
            s, l = feat.split("=")
            if val == 1:
                per[s]["is"] = l
            else:
                per[s]["not"].append(l)
        conds = []
        for s in core.SENSORS:
            if s not in per:
                continue
            if per[s]["is"]:
                conds.append({"sensor": s, "is": per[s]["is"]})
            else:
                conds.append({"sensor": s, "not": sorted(per[s]["not"])})
        out.append({"id": f"{prefix}{i}", "target": target, "if": conds})
    return out


def cond_text(cfg, cond):
    label = cfg["sensors"][cond["sensor"]]["label"]
    if "is" in cond:
        return f"{label} = {core.LEVEL_VI[cond['is']]}"
    return f"{label} ≠ " + "/".join(core.LEVEL_VI[x] for x in cond["not"])


def fit_all(df, cfg):
    """df đã có cột mức + cột nhãn. Trả về (fragment_rules, device_rules)."""
    X = to_matrix(df)
    names = list(X.columns)
    frag_rules, dev_rules = [], []
    for key, (fid, vi, kind) in FRAGMENTS.items():
        y = df["huong"].apply(lambda s: int(key in s.split("; ")[0:] or any(p.startswith(key) for p in s.split("; "))))
        tree = DecisionTreeClassifier(random_state=0).fit(X, y)
        for r in extract_rules(tree, names, fid, f"T_{fid}_"):
            frag_rules.append(r)
    for col, dev in DEVCOL.items():
        tree = DecisionTreeClassifier(random_state=0).fit(X, df[col])
        for r in extract_rules(tree, names, dev, f"L_{dev}_"):
            dev_rules.append(r)
    return frag_rules, dev_rules


def evaluate(df, rules):
    """So kết quả áp luật với nhãn thật trong CSV."""
    ok_dev = {d: 0 for d in core.DEVICES}
    ok_frag = 0
    n = len(df)
    truth_frag = df["huong"].apply(lambda s: {FRAGMENTS[k][0] for p in s.split("; ") for k in FRAGMENTS if p.startswith(k)})
    for i, (_, row) in enumerate(df.iterrows()):
        lv = {s: row[s] for s in core.SENSORS}
        fr, cmd, _ = core.apply_rules(rules, lv)
        for col, dev in DEVCOL.items():
            ok_dev[dev] += int(cmd[dev] == row[col])
        ok_frag += int(set(fr) == truth_frag.iloc[i])
    return {"mau": n, "tinh_huong_dung": ok_frag / n, **{f"lenh_{d}": ok_dev[d] / n for d in core.DEVICES}}


def main():
    csv = sys.argv[1] if len(sys.argv) > 1 else CSV_DEFAULT
    cfg = core.load_json(core.THRESHOLDS_PATH)
    raw = pd.read_csv(csv, encoding="utf-8-sig")
    df = raw.rename(columns=COLMAP)
    for s in core.SENSORS:
        df[s + "_val"] = df[s]
        df[s] = df[s + "_val"].apply(lambda v, s=s: core.get_level(cfg, s, v))
    df["huong"] = df["huong_xu_ly"]

    # kiểm tra thresholds.json có tái hiện đúng cột mức trong CSV không
    lvmap = {"Thap": "low", "TrungBinh": "mid", "Cao": "high", "Loi": "fault"}
    for src, s in [("temperature_level", "nhiet_do"), ("humidity_level", "do_am"),
                   ("light_level", "anh_sang"), ("water_level_level", "muc_nuoc")]:
        agree = (raw[src].map(lvmap) == df[s]).mean()
        print(f"[ngưỡng] {s}: khớp cột mức trong CSV {agree:.4%}")

    df["day"] = df["timestamp"].str[:10]
    days = sorted(df["day"].unique())
    tr = df[df["day"].isin(days[:5])]
    te = df[df["day"].isin(days[5:])]
    print(f"[tách] học {len(tr)} dòng ({days[0]}..{days[4]}), kiểm tra {len(te)} dòng ({days[5]}..{days[-1]})")
    fr, dv = fit_all(tr, cfg)
    ev = evaluate(te, {"fragment_rules": fr, "device_rules": dv})
    print("[kiểm tra trên ngày chưa thấy]", {k: round(v, 4) if isinstance(v, float) else v for k, v in ev.items()})

    fr, dv = fit_all(df, cfg)
    rules = {"fragment_rules": fr, "device_rules": dv}
    ev_all = evaluate(df, rules)
    print("[toàn bộ dữ liệu]", {k: round(v, 4) if isinstance(v, float) else v for k, v in ev_all.items()})

    support = collections.Counter(core.combo_key(row) for row in df[core.SENSORS].to_dict("records"))
    meta = {fid: {"vi": vi, "kind": kind} for _, (fid, vi, kind) in FRAGMENTS.items()}
    for r in rules["fragment_rules"] + rules["device_rules"]:
        r["text"] = " VÀ ".join(cond_text(cfg, c) for c in r["if"])
    out = {
        "nguon": os.path.basename(csv), "so_dong": int(len(df)),
        "danh_gia_ngay_chua_thay": ev, "danh_gia_toan_bo": ev_all,
        "fragments": meta, **rules, "combo_support": dict(support),
    }
    os.makedirs(os.path.dirname(core.RULES_PATH), exist_ok=True)
    with open(core.RULES_PATH, "w", encoding="utf-8") as f:
        json.dump(out, f, ensure_ascii=False, indent=1)
    print(f"[xong] {len(rules['fragment_rules'])} luật tình huống, {len(rules['device_rules'])} luật thiết bị -> {core.RULES_PATH}")


if __name__ == "__main__":
    main()
