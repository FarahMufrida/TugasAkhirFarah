# -*- coding: utf-8 -*-
import argparse
import os
import re
import sys
import sqlite3
from pathlib import Path
from urllib.parse import quote_plus

import pandas as pd
import pymysql
from sqlalchemy import create_engine

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics import accuracy_score, classification_report
from sklearn.model_selection import train_test_split
from sklearn.naive_bayes import ComplementNB

from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

sys.stdout.reconfigure(encoding="utf-8")
sys.stderr.reconfigure(encoding="utf-8")

BASE_DIR = Path(__file__).resolve().parents[1]


def log(level, message):
    print(f"[{level}] {message}", flush=True)


def fail(message, code=1):
    log("ERROR", message)
    sys.exit(code)


def parse_args():
    parser = argparse.ArgumentParser(description="Analisis sentimen ulasan untuk satu periode.")
    parser.add_argument("--periode-id", type=int, help="ID periode yang dianalisis. Default: periode terbaru.")
    return parser.parse_args()


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
    env = read_laravel_env()
    connection = env_value(env, "DB_CONNECTION", "mysql")

    if connection == "sqlite":
        database = env_value(env, "DB_DATABASE", str(BASE_DIR / "database" / "database.sqlite"))
        database_path = Path(database)
        if not database_path.is_absolute():
            database_path = BASE_DIR / database

        return {
            "connection": connection,
            "database": str(database_path),
        }

    if connection not in {"mysql", "mariadb"}:
        fail(f"DB_CONNECTION={connection} belum didukung oleh analisis.py. Gunakan sqlite/mysql/mariadb.")

    return {
        "connection": connection,
        "host": env_value(env, "DB_HOST", "127.0.0.1"),
        "port": int(env_value(env, "DB_PORT", "3306")),
        "database": env_value(env, "DB_DATABASE", "sistem_analisis"),
        "user": env_value(env, "DB_USERNAME", "root"),
        "password": env_value(env, "DB_PASSWORD", ""),
    }


def make_connections(config):
    if config["connection"] == "sqlite":
        engine = create_engine(f"sqlite:///{config['database']}")
        conn = sqlite3.connect(config["database"])
        conn.row_factory = sqlite3.Row
        return engine, conn

    engine_url = (
        "mysql+pymysql://"
        f"{quote_plus(config['user'])}:{quote_plus(config['password'])}"
        f"@{config['host']}:{config['port']}/{config['database']}?charset=utf8mb4"
    )
    engine = create_engine(engine_url)
    conn = pymysql.connect(
        host=config["host"],
        port=config["port"],
        user=config["user"],
        password=config["password"],
        database=config["database"],
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
    )
    return engine, conn


def is_sqlite_connection(conn):
    return isinstance(conn, sqlite3.Connection)


def prepare_sql(conn, sql):
    if is_sqlite_connection(conn):
        return sql.replace("%s", "?").replace("NOW()", "CURRENT_TIMESTAMP")
    return sql


def execute(cursor, conn, sql, params=()):
    cursor.execute(prepare_sql(conn, sql), params)


def table_columns(cursor, conn, table):
    if is_sqlite_connection(conn):
        cursor.execute(f"PRAGMA table_info({table})")
        return {row["name"] for row in cursor.fetchall()}

    cursor.execute(f"SHOW COLUMNS FROM {table}")
    return {row["Field"] for row in cursor.fetchall()}


SLANG_MAP = {
    "ga": "tidak",
    "gak": "tidak",
    "gk": "tidak",
    "nggak": "tidak",
    "ngga": "tidak",
    "ngak": "tidak",
    "bgt": "banget",
    "yg": "yang",
    "tp": "tapi",
}

POSITIF_WORDS = {
    "bagus", "indah", "mantap", "keren", "cantik", "menarik", "nyaman",
    "bersih", "recommended", "rekomendasi", "suka", "senang", "puas",
    "murah", "asyik", "ramah", "worth", "spektakuler", "memukau",
    "sejuk", "kece", "amazing", "beautiful", "good", "nice", "best",
    "great", "perfect", "recommend", "memuaskan", "menyenangkan", "view",
    "seru", "enak", "adem",
}

NEGATIF_WORDS = {
    "tidak", "buruk", "mahal", "jelek", "kotor", "kecewa", "rusak",
    "sempit", "panas", "bau", "berbahaya", "sepi", "bosan",
    "mengecewakan", "payah", "parah", "jorok", "macet", "antri",
    "penuh", "sampah", "sayang", "kurang", "susah", "sulit", "jauh",
    "capek", "lelah",
}


stemmer = StemmerFactory().create_stemmer()
stopwords = set(StopWordRemoverFactory().get_stop_words())
stopwords.discard("tidak")
stopwords.discard("bukan")
stopwords.discard("jangan")


def normalize_rating(value):
    if pd.isna(value):
        return None
    match = re.search(r"([1-5])", str(value))
    return int(match.group(1)) if match else None


def preprocess_text(text):
    text = str(text).lower()
    text = re.sub(r"https?://\S+|www\.\S+", " ", text)
    text = re.sub(r"[^a-z\s]", " ", text)
    text = re.sub(r"\s+", " ", text).strip()

    words = [SLANG_MAP.get(word, word) for word in text.split()]
    words = [word for word in words if word not in stopwords and len(word) > 2]

    return stemmer.stem(" ".join(words)).strip()


# def label_by_keyword(clean_text):
#     words = set(clean_text.split())
#     positive_score = len(words & POSITIF_WORDS)
#     negative_score = len(words & NEGATIF_WORDS)

#     if positive_score > negative_score:
#         return "positif"
#     if negative_score > positive_score:
#         return "negatif"
#     return "netral"


def label_by_keyword(clean_text):
    words = clean_text.split()

    positive_score = sum(1 for word in words if word in POSITIF_WORDS)
    negative_score = sum(1 for word in words if word in NEGATIF_WORDS)

    if positive_score > negative_score:
        return "positif"

    elif negative_score > positive_score:
        return "negatif"

    return "netral"


# def make_pseudo_label(row):
#     rating = normalize_rating(row.get("rating"))
#     if rating is not None:
#         if rating >= 4:
#             return "positif"
#         if rating == 3:
#             return "netral"
#         return "negatif"

#     return label_by_keyword(row["ulasan_bersih"])

def make_pseudo_label(row):
    return label_by_keyword(row["ulasan_bersih"])


def rating_confidence(value):
    rating = normalize_rating(value)
    if rating is None:
        return None
    if rating in {1, 5}:
        return 1.0
    if rating in {2, 4}:
        return 0.85
    return 0.7


def apply_rating_priority(row, model_classes=None):
    sentiment_result = row["sentimen"]
    probability = float(row["probabilitas"])

    # Jika probabilitas model sangat rendah (di bawah 0.5), baru gunakan rating
    if probability < 0.5:
        rating_label = make_pseudo_label(row)
        return rating_label, 0.5
    
    return sentiment_result, probability

    # rating_label = make_pseudo_label(row)
    # rating = normalize_rating(row.get("rating"))
    # if rating is None:
    #     return row["sentimen"], float(row["probabilitas"])

    if row["sentimen"] != rating_label:
        log(
            "INFO",
            f"Override sentimen berdasarkan rating {rating}: model={row['sentimen']} -> final={rating_label}",
        )

    probability = rating_confidence(rating)
    if model_classes is not None and rating_label in model_classes:
        try:
            class_index = list(model_classes).index(rating_label)
            probability = max(float(row["probabilitas_by_class"][class_index]), probability)
        except Exception:
            pass

    return rating_label, probability


def main():
    args = parse_args()
    config = db_config()
    if config["connection"] == "sqlite":
        log("INFO", f"Menggunakan database SQLite {config['database']}")
    else:
        log("INFO", f"Menggunakan database {config['database']} di {config['host']}:{config['port']}")

    engine, raw_conn = make_connections(config)
    cursor = raw_conn.cursor()

    try:
        if args.periode_id:
            execute(cursor, raw_conn, "SELECT id, nama FROM periode_analisis WHERE id = %s LIMIT 1", (args.periode_id,))
        else:
            execute(cursor, raw_conn, """
                SELECT p.id, p.nama
                FROM periode_analisis p
                WHERE EXISTS (
                    SELECT 1 FROM ulasan u WHERE u.periode_id = p.id
                )
                ORDER BY p.id DESC
                LIMIT 1
            """)
        periode = cursor.fetchone()
        if not periode:
            fail("Belum ada periode yang memiliki ulasan. Jalankan Ambil Data terlebih dahulu.")

        periode_id = periode["id"]
        periode_nama = periode["nama"]
        log("INFO", f"Analisis periode terbaru: {periode_nama} (periode_id={periode_id})")

        df = pd.read_sql(
            prepare_sql(
                raw_conn,
                "SELECT u.id, u.wisata, u.reviewer, u.rating, u.ulasan, u.tanggal, u.periode_id "
                 "FROM ulasan u WHERE u.periode_id = %s AND NOT EXISTS ( SELECT 1 FROM hasil_analisis h WHERE h.ulasan_id = u.id  )"
            ),
            
            engine,
            params=(periode_id,),
        )

        if df.empty:
            fail(f"Tidak ada data ulasan untuk periode_id={periode_id}.")

        df = df.dropna(subset=["ulasan"]).copy()
        df["ulasan"] = df["ulasan"].astype(str)
        df = df[df["ulasan"].str.strip().ne("")]
        df = df[df["ulasan"].str.strip().ne("0")]
        df = df[~df["ulasan"].str.contains(r"\[Tanpa teks\]", na=False)]
        df = df[df["ulasan"].str.len() > 5]

        if df.empty:
            fail("Data ulasan kosong setelah validasi teks.")

        df["ulasan_bersih"] = df["ulasan"].apply(preprocess_text)
        df = df[df["ulasan_bersih"].str.strip().ne("")].copy()

        if df.empty:
            fail("Data kosong setelah preprocessing. Tidak ada teks yang bisa dianalisis.")

        df["label"] = df.apply(make_pseudo_label, axis=1)
        label_counts = df["label"].value_counts()
        log("INFO", "Distribusi pseudo-label: " + ", ".join(f"{k}={v}" for k, v in label_counts.items()))

        use_model = True
        if label_counts.size < 2:
            use_model = False
            log("WARNING", "Jumlah kelas kurang dari 2. Prediksi memakai pseudo-label langsung tanpa training model.")

        can_stratify = label_counts.min() >= 2
        if not can_stratify:
            log("WARNING", "Ada kelas dengan jumlah data kurang dari 2. Split evaluasi dibuat tanpa stratify.")

        report = {"weighted avg": {"precision": 0, "recall": 0, "f1-score": 0}}
        accuracy = 0

        if use_model:
            X = df["ulasan_bersih"]
            y = df["label"]

            if len(df) >= 5:
                X_train, X_test, y_train, y_test = train_test_split(
                    X,
                    y,
                    test_size=0.2,
                    random_state=42,
                    stratify=y if can_stratify else None,
                )
            else:
                log("WARNING", "Data kurang dari 5 baris. Evaluasi memakai data latih yang sama.")
                X_train, X_test, y_train, y_test = X, X, y, y

            vectorizer = TfidfVectorizer(max_features=5000, ngram_range=(1, 2))
            X_train_vec = vectorizer.fit_transform(X_train)
            X_test_vec = vectorizer.transform(X_test)

            model = ComplementNB()
            model.fit(X_train_vec, y_train)

            y_pred = model.predict(X_test_vec)
            report = classification_report(y_test, y_pred, output_dict=True, zero_division=0)
            accuracy = accuracy_score(y_test, y_pred)

            X_all_vec = vectorizer.transform(df["ulasan_bersih"])
            df["sentimen"] = model.predict(X_all_vec)
            probability_matrix = model.predict_proba(X_all_vec)
            df["probabilitas"] = probability_matrix.max(axis=1)
            df["probabilitas"] = df["probabilitas"].clip(upper=0.99)
            df["probabilitas_by_class"] = list(probability_matrix)
            final_results = df.apply(lambda row: apply_rating_priority(row, model.classes_), axis=1)
            df["sentimen"] = [result[0] for result in final_results]
            df["probabilitas"] = [result[1] for result in final_results]
            df = df.drop(columns=["probabilitas_by_class"])
        else:
            df["sentimen"] = df["label"]
            df["probabilitas"] = df["rating"].apply(lambda rating: rating_confidence(rating) or 0.7)

        log("INFO", "Evaluasi memakai pseudo-label dari rating/rule otomatis, bukan label manual.")

        # execute(cursor, raw_conn, "DELETE FROM hasil_analisis WHERE periode_id = %s", (periode_id,))
        # execute(cursor, raw_conn, "DELETE FROM evaluasi_model WHERE periode_id = %s", (periode_id,))

        hasil_columns = table_columns(cursor, raw_conn, "hasil_analisis")
        insert_columns = [
            "ulasan_id",
            "wisata",
            "ulasan_asli",
            "ulasan_bersih",
            "hasil_preprocessing",
            "sentimen",
            "probabilitas",
            "periode_id",
            "created_at",
            "updated_at",
        ]
        if "ulasan_terolah" in hasil_columns:
            insert_columns.insert(3, "ulasan_terolah")

        placeholders = ", ".join(["%s"] * (len(insert_columns) - 2) + ["NOW()", "NOW()"])
        insert_hasil = f"""
            INSERT INTO hasil_analisis ({", ".join(insert_columns)})
            VALUES ({placeholders})
        """

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

            execute(cursor, raw_conn, insert_hasil, tuple(values))

        weighted = report.get("weighted avg", {})
        execute(
            cursor,
            raw_conn,
            """
            INSERT INTO evaluasi_model
            (`precision`, `recall`, f1_score, accuracy, tp, tn, fp, fn, periode_id, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
            """,
            (
                float(weighted.get("precision", 0)),
                float(weighted.get("recall", 0)),
                float(weighted.get("f1-score", 0)),
                float(accuracy),
                0,
                0,
                0,
                0,
                periode_id,
            ),
        )

        raw_conn.commit()
        log("OK", f"{len(df)} hasil analisis disimpan untuk periode {periode_nama}.")
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
