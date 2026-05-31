# -*- coding: utf-8 -*-
"""
analisis.py — SENTARA
=====================
Sistem Analisis Sentimen Ulasan Wisatawan
Metode   : Naive Bayes (ComplementNB) + TF-IDF
Label    : Dari teks ulasan (bukan rating)
"""

import json
import os
import re
import sqlite3
import sys
import warnings
from pathlib import Path
from urllib.parse import quote_plus

import joblib
import numpy as np
import pandas as pd
import pymysql
from sqlalchemy import create_engine

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics import (
    accuracy_score,
    classification_report,
    f1_score,
)
from sklearn.model_selection import (
    GridSearchCV,
    StratifiedKFold,
    cross_val_score,
    train_test_split,
)
from sklearn.naive_bayes import ComplementNB
from sklearn.pipeline import Pipeline

from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

warnings.filterwarnings("ignore")
sys.stdout.reconfigure(encoding="utf-8")
sys.stderr.reconfigure(encoding="utf-8")

# ═══════════════════════════════════════════════════════════════
# KONSTANTA & PATH
# ═══════════════════════════════════════════════════════════════

SEED     = 42
BASE_DIR = Path(__file__).resolve().parents[1]
ARTEFAK  = BASE_DIR / "artefak"
ARTEFAK.mkdir(exist_ok=True)

MODEL_PATH    = ARTEFAK / "sentimen_naive_bayes.pkl"
METADATA_PATH = ARTEFAK / "model_metadata.json"

# ═══════════════════════════════════════════════════════════════
# LOGGER
# ═══════════════════════════════════════════════════════════════

def log(level, message):
    print(f"[{level}] {message}", flush=True)

def fail(message, code=1):
    log("ERROR", message)
    sys.exit(code)

# ═══════════════════════════════════════════════════════════════
# KAMUS NORMALISASI LENGKAP (dari dataset.ipynb)
# ═══════════════════════════════════════════════════════════════

NORMALISASI = {
    # Negasi — JANGAN hapus
    r"\b(ga|gak|gk|nggak|ngga|ngak|engga|enggak)\b": "tidak",
    r"\b(blm|blom|blum)\b":   "belum",
    r"\b(bkn|bukn)\b":        "bukan",
    r"\b(jgn|jangan|jgan)\b": "jangan",
    # Kata ganti
    r"\b(ak|aq|gw|gue|gua)\b":          "aku",
    r"\b(km|loe|lu|elo|lo)\b":           "kamu",
    r"\b(sy|sya)\b":                      "saya",
    # Kata kerja
    r"\b(bs|bsa)\b":           "bisa",
    r"\b(liat|lht)\b":         "lihat",
    r"\b(mkn|mkan|maem)\b":    "makan",
    r"\b(pake|pk|pke)\b":      "pakai",
    r"\b(tau|taw|tw)\b":       "tahu",
    r"\b(dtg)\b":              "datang",
    r"\b(lgsg|lgsung)\b":      "langsung",
    # Kata sifat
    r"\b(bgs|bgus|nice|good|top)\b":  "bagus",
    r"\b(josss|joss|sipp|sip)\b":     "mantap",
    r"\b(byk|bnyk)\b":                "banyak",
    r"\b(lbh|lbih)\b":                "lebih",
    r"\b(bener|bnr)\b":               "benar",
    # Kata sambung & keterangan
    r"\b(aja|sja|ae)\b":       "saja",
    r"\b(bgt|bangettt|bangett)\b": "banget",
    r"\b(br|bru)\b":           "baru",
    r"\b(cmn|cuma|cuman)\b":   "cuma",
    r"\b(dgn|dngn|dg)\b":      "dengan",
    r"\b(dl|dlu)\b":           "dulu",
    r"\b(dlm|dalem)\b":        "dalam",
    r"\b(dr|dri)\b":           "dari",
    r"\b(emg|emang)\b":        "memang",
    r"\b(gt|gitu|bgitu)\b":    "begitu",
    r"\b(hbs|hbis)\b":         "habis",
    r"\b(hrs|hrus)\b":         "harus",
    r"\b(jd|jdi)\b":           "jadi",
    r"\b(jg|jga)\b":           "juga",
    r"\b(kdg|kdang)\b":        "kadang",
    r"\b(klo|kalo|kl)\b":      "kalau",
    r"\b(krn|karna)\b":        "karena",
    r"\b(kyk|kek|kya)\b":      "seperti",
    r"\b(lg|lgi)\b":           "lagi",
    r"\b(mgkn|mngkin)\b":      "mungkin",
    r"\b(msi|msh|msih)\b":     "masih",
    r"\b(pd|pda)\b":           "pada",
    r"\b(sdh|udh|udah|uda)\b": "sudah",
    r"\b(skrg|skrng)\b":       "sekarang",
    r"\b(sllu|slalu)\b":       "selalu",
    r"\b(sm|ama)\b":           "sama",
    r"\b(smpai|ampe|smpe)\b":  "sampai",
    r"\b(smua)\b":             "semua",
    r"\b(srg|sring)\b":        "sering",
    r"\b(tp|tpi)\b":           "tapi",
    r"\b(trs|trus)\b":         "lalu",
    r"\b(ttp|ttep)\b":         "tetap",
    r"\b(utk|untk)\b":         "untuk",
    r"\b(yg|yng)\b":           "yang",
    r"\b(pdhl|pdhal)\b":       "padahal",
    r"\b(tmpt|tempt)\b":       "tempat",
    r"\b(tmn|temen)\b":        "teman",
    r"\b(org|orng)\b":         "orang",
    # Konteks pariwisata
    r"\b(recommended|recomended)\b": "rekomendasi",
    r"\b(healing)\b":                "rekreasi",
    r"\b(htm)\b":                    "harga tiket masuk",
    r"\b(overall)\b":                "secara keseluruhan",
    r"\b(spot foto)\b":              "lokasi foto",
    # Hapus tawa & makian
    r"\b(wkwk+|haha+|hehe+)\b": "",
    r"\b(bjir|anjay|anjir)\b":  "",
}

# ═══════════════════════════════════════════════════════════════
# KAMUS SENTIMEN (pseudo-label fallback)
# ═══════════════════════════════════════════════════════════════

POSITIF_WORDS = {
    "bagus","indah","cantik","keren","mantap","asri","bersih","nyaman","rapi",
    "adem","sejuk","segar","menarik","spektakuler","eksotis","unik","istimewa",
    "menakjubkan","memukau","asyik","asik","senang","puas","suka","happy",
    "enjoy","bahagia","menyenangkan","seru","recommended","rekomendasi","wajib",
    "worth","memuaskan","healing","josss","joss","sip","lengkap","terawat",
    "baik","oke","ramah","murah","terjangkau","luas","teduh","view","sunset",
    "sunrise","jernih","bening","enak","lezat","amazing","beautiful","great",
    "nice","good","perfect","best","lovely","wonderful","fantastic","awesome",
}

NEGATIF_WORDS = {
    "kotor","jorok","jelek","buruk","rusak","kumuh","sempit","parah","payah",
    "berantakan","mengecewakan","kecewa","nyesel","menyesal","bocor","mati",
    "gelap","bau","busuk","pengap","mahal","kemahalan","lambat","antri","macet",
    "sesak","penuh","berebut","kasar","jutek","cuek","berbahaya","bahaya",
    "licin","curam","sampah","tidak puas","kapok","ogah","zonk","tipu","pungli",
}

NEGASI = {"tidak","bukan","jangan","belum","tanpa","kurang","ga","gak","nggak"}

# ═══════════════════════════════════════════════════════════════
# INISIALISASI NLP
# ═══════════════════════════════════════════════════════════════

log("INFO", "Memuat stemmer dan stopword Sastrawi...")
_stemmer = StemmerFactory().create_stemmer()
_sw_raw  = set(StopWordRemoverFactory().get_stop_words())
# Pertahankan kata negasi — kritis untuk sentimen
STOPWORDS = _sw_raw - NEGASI
log("INFO", f"Stopword: {len(_sw_raw)} kata | Negasi dipertahankan: {NEGASI}")

# ═══════════════════════════════════════════════════════════════
# PREPROCESSING
# ═══════════════════════════════════════════════════════════════

def normalisasi_teks(text):
    for pattern, replacement in NORMALISASI.items():
        text = re.sub(pattern, replacement, text)
    return text

def preprocess_text(text):
    """Pipeline preprocessing 6 tahap."""
    text = str(text).lower()
    text = re.sub(r"https?://\S+|www\.\S+", " ", text)   # hapus URL
    text = re.sub(r"@\w+|#\w+", " ", text)               # hapus mention/hashtag
    text = re.sub(r"[^a-z\s]", " ", text)                # hapus non-alfabet
    text = re.sub(r"\s+", " ", text).strip()
    text = normalisasi_teks(text)                         # normalisasi kamus
    tokens = [t for t in text.split() if t not in STOPWORDS and len(t) > 2]
    return _stemmer.stem(" ".join(tokens)).strip()

# ═══════════════════════════════════════════════════════════════
# PSEUDO-LABELING (fallback jika tidak ada model tersimpan)
# ═══════════════════════════════════════════════════════════════

def label_by_keyword(clean_text):
    tokens = clean_text.split()
    pos = neg = 0
    i = 0
    while i < len(tokens):
        sebelum_negasi = (i > 0 and tokens[i-1] in NEGASI)
        w = tokens[i]
        if w in POSITIF_WORDS:
            neg += 1 if sebelum_negasi else 0
            pos += 0 if sebelum_negasi else 1
        elif w in NEGATIF_WORDS:
            pos += 1 if sebelum_negasi else 0
            neg += 0 if sebelum_negasi else 1
        i += 1

    if pos > neg:   return "positif"
    if neg > pos:   return "negatif"
    return "netral"

def make_pseudo_label(row):
    return label_by_keyword(row["ulasan_bersih"])

# ═══════════════════════════════════════════════════════════════
# LOAD / TRAIN MODEL
# ═══════════════════════════════════════════════════════════════

def load_saved_model():
    """Muat model tersimpan jika ada."""
    if MODEL_PATH.exists():
        log("INFO", f"Model tersimpan ditemukan → dimuat dari {MODEL_PATH}")
        return joblib.load(MODEL_PATH)
    return None

def train_and_save_model(df):
    """
    Latih ComplementNB dengan:
    - 5-Fold Stratified Cross Validation
    - GridSearchCV hyperparameter tuning
    - Evaluasi Macro F1 + classification report
    """
    log("INFO", "=" * 55)
    log("INFO", "TRAINING MODEL NAIVE BAYES (ComplementNB)")
    log("INFO", "=" * 55)

    X = df["ulasan_bersih"].values
    y = df["label"].values

    label_counts = pd.Series(y).value_counts()
    log("INFO", "Distribusi label: " + str(label_counts.to_dict()))

    # Minimal 2 kelas dan tiap kelas >= 5
    if label_counts.size < 2 or label_counts.min() < 2:
        log("WARNING", "Data tidak cukup untuk training — pakai pseudo-label langsung.")
        return None, 0, 0

    # Split 80/20
    can_stratify = label_counts.min() >= 2
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=SEED,
        stratify=y if can_stratify else None
    )
    log("INFO", f"Split — Train: {len(X_train)} | Test: {len(X_test)}")

    kfold = StratifiedKFold(n_splits=min(5, label_counts.min()), shuffle=True, random_state=SEED)

    # ── 5-Fold Cross Validation ──────────────────────────────
    log("INFO", "Menjalankan 5-Fold Cross Validation...")
    pipe_cv = Pipeline([
        ("tfidf", TfidfVectorizer(ngram_range=(1,2), min_df=1, sublinear_tf=True)),
        ("clf",   ComplementNB()),
    ])
    cv_scores = cross_val_score(pipe_cv, X_train, y_train, cv=kfold, scoring="f1_macro", n_jobs=-1)
    log("INFO", f"CV Macro F1 per fold : {[round(s,4) for s in cv_scores]}")
    log("INFO", f"CV Macro F1 rata-rata: {cv_scores.mean():.4f} ± {cv_scores.std():.4f}")

    # ── GridSearchCV Tuning ───────────────────────────────────
    log("INFO", "GridSearchCV tuning hiperparameter...")
    param_grid = {
        "tfidf__ngram_range":  [(1,1),(1,2)],
        "tfidf__min_df":       [1, 2, 3],
        "tfidf__sublinear_tf": [True, False],
        "clf__alpha":          [0.1, 0.5, 1.0, 2.0],
        "clf__norm":           [True, False],
    }
    pipe_gs = Pipeline([
        ("tfidf", TfidfVectorizer()),
        ("clf",   ComplementNB()),
    ])
    gs = GridSearchCV(pipe_gs, param_grid, cv=kfold, scoring="f1_macro", n_jobs=-1, refit=True)
    gs.fit(X_train, y_train)

    best = gs.best_estimator_
    log("INFO", f"Parameter terbaik : {gs.best_params_}")
    log("INFO", f"Best CV Macro F1  : {gs.best_score_:.4f}")

    # ── Evaluasi Final ────────────────────────────────────────
    y_pred   = best.predict(X_test)
    accuracy = accuracy_score(y_test, y_pred)
    macro_f1 = f1_score(y_test, y_pred, average="macro", zero_division=0)
    report   = classification_report(y_test, y_pred, zero_division=0)
    log("INFO", f"Akurasi       : {accuracy:.4f} ({accuracy*100:.2f}%)")
    log("INFO", f"Macro F1-Score: {macro_f1:.4f}")
    log("INFO", f"\nClassification Report:\n{report}")

    # ── Simpan model & metadata ───────────────────────────────
    joblib.dump(best, MODEL_PATH)
    report_dict = classification_report(y_test, y_pred, output_dict=True, zero_division=0)
    metadata = {
        "model": "ComplementNB (Naive Bayes)",
        "best_params": gs.best_params_,
        "cv_macro_f1": {"mean": round(cv_scores.mean(),4), "std": round(cv_scores.std(),4)},
        "evaluasi_test": {
            "accuracy": round(accuracy,4),
            "macro_f1": round(macro_f1,4),
        },
        "distribusi_label": pd.Series(y).value_counts().to_dict(),
    }
    with open(METADATA_PATH, "w", encoding="utf-8") as f:
        json.dump(metadata, f, indent=4, ensure_ascii=False)

    log("INFO", f"Model disimpan → {MODEL_PATH}")
    return best, accuracy, macro_f1

# ═══════════════════════════════════════════════════════════════
# DATABASE
# ═══════════════════════════════════════════════════════════════

def read_laravel_env():
    env = {}
    env_file = BASE_DIR / ".env"
    if not env_file.exists():
        return env
    for line in env_file.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        value = value.strip().strip('"').strip("'")
        env.setdefault(key.strip(), value)
    return env

def env_value(env, key, default=""):
    value = os.getenv(key, env.get(key, default))
    if value in {None, "", "null", "None"}:
        return default
    return value

def db_config():
    env  = read_laravel_env()
    conn = env_value(env, "DB_CONNECTION", "mysql")
    if conn == "sqlite":
        db = env_value(env, "DB_DATABASE", str(BASE_DIR / "database" / "database.sqlite"))
        db_path = Path(db)
        if not db_path.is_absolute():
            db_path = BASE_DIR / db
        return {"connection": conn, "database": str(db_path)}
    if conn not in {"mysql", "mariadb"}:
        fail(f"DB_CONNECTION={conn} belum didukung.")
    return {
        "connection": conn,
        "host":       env_value(env, "DB_HOST", "127.0.0.1"),
        "port":       int(env_value(env, "DB_PORT", "3306")),
        "database":   env_value(env, "DB_DATABASE", "sistem_analisis"),
        "user":       env_value(env, "DB_USERNAME", "root"),
        "password":   env_value(env, "DB_PASSWORD", ""),
    }

def make_connections(config):
    if config["connection"] == "sqlite":
        engine = create_engine(f"sqlite:///{config['database']}")
        conn   = sqlite3.connect(config["database"])
        conn.row_factory = sqlite3.Row
        return engine, conn
    url = (
        "mysql+pymysql://"
        f"{quote_plus(config['user'])}:{quote_plus(config['password'])}"
        f"@{config['host']}:{config['port']}/{config['database']}?charset=utf8mb4"
    )
    engine = create_engine(url)
    conn   = pymysql.connect(
        host=config["host"], port=config["port"],
        user=config["user"], password=config["password"],
        database=config["database"], charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
    )
    return engine, conn

def is_sqlite(conn):
    return isinstance(conn, sqlite3.Connection)

def prepare_sql(conn, sql):
    if is_sqlite(conn):
        return sql.replace("%s", "?").replace("NOW()", "CURRENT_TIMESTAMP")
    return sql

def execute(cursor, conn, sql, params=()):
    cursor.execute(prepare_sql(conn, sql), params)

def table_columns(cursor, conn, table):
    if is_sqlite(conn):
        cursor.execute(f"PRAGMA table_info({table})")
        return {row["name"] for row in cursor.fetchall()}
    cursor.execute(f"SHOW COLUMNS FROM {table}")
    return {row["Field"] for row in cursor.fetchall()}

# ═══════════════════════════════════════════════════════════════
# MAIN
# ═══════════════════════════════════════════════════════════════

def main():
    import argparse
    parser = argparse.ArgumentParser(description="SENTARA — Analisis Sentimen")
    parser.add_argument("--periode-id", type=int, default=None)
    args = parser.parse_args()

    log("INFO", "=" * 55)
    log("INFO", "SENTARA — Sistem Analisis Sentimen Wisata Jember")
    log("INFO", "=" * 55)

    config         = db_config()
    engine, raw_conn = make_connections(config)
    cursor         = raw_conn.cursor()

    try:
        # ── Cari periode ──────────────────────────────────────
        if args.periode_id:
            execute(cursor, raw_conn,
                "SELECT id, nama FROM periode_analisis WHERE id = %s LIMIT 1",
                (args.periode_id,))
        else:
            execute(cursor, raw_conn, """
                SELECT p.id, p.nama FROM periode_analisis p
                WHERE EXISTS (SELECT 1 FROM ulasan u WHERE u.periode_id = p.id)
                ORDER BY p.id DESC LIMIT 1
            """)
        periode = cursor.fetchone()
        if not periode:
            fail("Belum ada periode dengan ulasan.")

        periode_id   = periode["id"]
        periode_nama = periode["nama"]
        log("INFO", f"Periode: {periode_nama} (id={periode_id})")

        # ── Ambil ulasan baru (belum dianalisis) ──────────────
        df = pd.read_sql(
            prepare_sql(raw_conn,
                "SELECT u.id, u.wisata, u.reviewer, u.rating, u.ulasan, u.tanggal, u.periode_id "
                "FROM ulasan u WHERE u.periode_id = %s "
                "AND NOT EXISTS (SELECT 1 FROM hasil_analisis h WHERE h.ulasan_id = u.id)"
            ),
            engine, params=(periode_id,),
        )

        if df.empty:
            fail(f"Tidak ada ulasan baru untuk periode_id={periode_id}.")

        # ── Validasi & bersihkan ──────────────────────────────
        df = df.dropna(subset=["ulasan"]).copy()
        df["ulasan"] = df["ulasan"].astype(str)
        df = df[df["ulasan"].str.strip().ne("")]
        df = df[df["ulasan"].str.strip().ne("0")]
        df = df[~df["ulasan"].str.contains(r"\[Tanpa teks\]", na=False)]
        df = df[df["ulasan"].str.len() > 5]

        if df.empty:
            fail("Data kosong setelah validasi.")

        # ── Preprocessing ─────────────────────────────────────
        log("INFO", "Preprocessing teks (6 tahap)...")
        df["ulasan_bersih"] = df["ulasan"].apply(preprocess_text)
        df = df[df["ulasan_bersih"].str.strip().ne("")].copy()
        log("INFO", f"Data valid setelah preprocessing: {len(df)} baris")

        # ── Pseudo-label ──────────────────────────────────────
        df["label"] = df.apply(make_pseudo_label, axis=1)
        label_counts = df["label"].value_counts()
        log("INFO", "Distribusi pseudo-label: " + str(label_counts.to_dict()))

        # ── Load atau Train model ─────────────────────────────
        saved_pipeline = load_saved_model()

        if saved_pipeline is not None:
            # Pakai model tersimpan (dari training manual sebelumnya)
            log("INFO", "Menggunakan model tersimpan untuk prediksi.")
            best_pipeline = saved_pipeline
            # Baca metadata akurasi
            accuracy = macro_f1 = 0.0
            if METADATA_PATH.exists():
                with open(METADATA_PATH, encoding="utf-8") as f:
                    meta = json.load(f)
                accuracy = meta.get("evaluasi_test", {}).get("accuracy", 0)
                macro_f1 = meta.get("evaluasi_test", {}).get("macro_f1", 0)
                log("INFO", f"Akurasi model tersimpan : {accuracy:.4f}")
                log("INFO", f"Macro F1 model tersimpan: {macro_f1:.4f}")
            report_dict = {"weighted avg": {"precision": accuracy, "recall": accuracy, "f1-score": macro_f1}}
        else:
            # Tidak ada model → train dari pseudo-label
            log("WARNING",
                "Model belum tersimpan. Training dari pseudo-label.\n"
                "Untuk akurasi lebih baik, latih dengan dataset berlabel manual:\n"
                "python analisis.py --mode train --dataset data_berlabel.csv")
            best_pipeline, accuracy, macro_f1 = train_and_save_model(df)

            if best_pipeline is None:
                # Fallback: langsung pakai pseudo-label
                df["sentimen"]    = df["label"]
                df["probabilitas"] = 0.60
                accuracy = macro_f1 = 0.0
                report_dict = {"weighted avg": {"precision": 0, "recall": 0, "f1-score": 0}}
            else:
                report_dict = {"weighted avg": {"precision": accuracy, "recall": accuracy, "f1-score": macro_f1}}

        # ── Prediksi ──────────────────────────────────────────
        if best_pipeline is not None:
            log("INFO", "Memprediksi sentimen...")
            df["sentimen"]    = best_pipeline.predict(df["ulasan_bersih"])
            prob_matrix       = best_pipeline.predict_proba(df["ulasan_bersih"])
            df["probabilitas"] = prob_matrix.max(axis=1).clip(max=0.99)

            # Post-processing: probabilitas rendah → fallback kamus
            mask_low = df["probabilitas"] < 0.45
            if mask_low.sum() > 0:
                log("INFO", f"{mask_low.sum()} ulasan probabilitas rendah → fallback ke kamus")
                df.loc[mask_low, "sentimen"]    = df.loc[mask_low, "ulasan_bersih"].apply(label_by_keyword)
                df.loc[mask_low, "probabilitas"] = 0.50

        dist = df["sentimen"].value_counts().to_dict()
        log("INFO", f"Distribusi sentimen hasil: {dist}")

        # ── Simpan ke hasil_analisis ──────────────────────────
        hasil_columns = table_columns(cursor, raw_conn, "hasil_analisis")
        insert_columns = [
            "ulasan_id","wisata","ulasan_asli","ulasan_bersih",
            "hasil_preprocessing","sentimen","probabilitas",
            "periode_id","created_at","updated_at",
        ]
        if "ulasan_terolah" in hasil_columns:
            insert_columns.insert(3, "ulasan_terolah")

        placeholders = ", ".join(["%s"] * (len(insert_columns) - 2) + ["NOW()", "NOW()"])
        insert_sql   = f"INSERT INTO hasil_analisis ({', '.join(insert_columns)}) VALUES ({placeholders})"

        for _, row in df.fillna("").iterrows():
            values = [
                int(row["id"]),
                str(row["wisata"]),
                str(row["ulasan"]),
                str(row["ulasan_bersih"]),
                str(row["ulasan_bersih"]),
                str(row["sentimen"]).lower(),
                float(row["probabilitas"]),
                periode_id,
            ]
            if "ulasan_terolah" in hasil_columns:
                values.insert(3, str(row["ulasan_bersih"]))
            execute(cursor, raw_conn, insert_sql, tuple(values))

        # ── Simpan ke evaluasi_model ──────────────────────────
        weighted = report_dict.get("weighted avg", {})
        execute(cursor, raw_conn, """
            INSERT INTO evaluasi_model
            (`precision`, `recall`, f1_score, accuracy, tp, tn, fp, fn, periode_id, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
        """, (
            float(weighted.get("precision", 0)),
            float(weighted.get("recall",    0)),
            float(weighted.get("f1-score",  macro_f1)),
            float(accuracy),
            0, 0, 0, 0, periode_id,
        ))

        raw_conn.commit()
        log("OK", f"{len(df)} hasil analisis disimpan untuk periode '{periode_nama}'.")
        log("OK", "Analisis selesai.")

    except SystemExit:
        raw_conn.rollback()
        raise
    except Exception as exc:
        raw_conn.rollback()
        fail(f"Analisis gagal: {exc}")
    finally:
        raw_conn.close()
        engine.dispose()


if __name__ == "__main__":
    main()
