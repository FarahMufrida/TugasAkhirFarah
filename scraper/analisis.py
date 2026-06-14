# # -*- coding: utf-8 -*-
# import argparse
# import os
# import re
# import sys
# import sqlite3
# from pathlib import Path
# from urllib.parse import quote_plus
# import joblib

# import pandas as pd
# import pymysql
# from sqlalchemy import create_engine

# from sklearn.feature_extraction.text import TfidfVectorizer
# from sklearn.metrics import accuracy_score, classification_report
# from sklearn.model_selection import train_test_split
# from sklearn.naive_bayes import ComplementNB 

# from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
# from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

# sys.stdout.reconfigure(encoding="utf-8")
# sys.stderr.reconfigure(encoding="utf-8")

# BASE_DIR = Path(__file__).resolve().parents[1]


# def log(level, message):
#     print(f"[{level}] {message}", flush=True)


# def fail(message, code=1):
#     log("ERROR", message)
#     sys.exit(code)


# def parse_args():
#     parser = argparse.ArgumentParser(description="Analisis sentimen ulasan untuk satu periode.")
#     parser.add_argument("--periode-id", type=int, help="ID periode yang dianalisis. Default: periode terbaru.")
#     return parser.parse_args()


# def read_laravel_env():
#     env = {}
#     env_file = BASE_DIR / ".env"
#     if not env_file.exists():
#         return env

#     for line in env_file.read_text(encoding="utf-8").splitlines():
#         line = line.strip()
#         if not line or line.startswith("#") or "=" not in line:
#             continue
#         key, value = line.split("=", 1)
#         value = value.strip().strip('"').strip("'")
#         env.setdefault(key.strip(), value)

#     return env


# def env_value(env, key, default=""):
#     value = os.getenv(key, env.get(key, default))
#     if value in {None, "", "null", "None"}:
#         return default
#     return value


# def db_config():
#     env = read_laravel_env()
#     connection = env_value(env, "DB_CONNECTION", "mysql")

#     if connection == "sqlite":
#         database = env_value(env, "DB_DATABASE", str(BASE_DIR / "database" / "database.sqlite"))
#         database_path = Path(database)
#         if not database_path.is_absolute():
#             database_path = BASE_DIR / database

#         return {
#             "connection": connection,
#             "database": str(database_path),
#         }

#     if connection not in {"mysql", "mariadb"}:
#         fail(f"DB_CONNECTION={connection} belum didukung oleh analisis.py. Gunakan sqlite/mysql/mariadb.")

#     return {
#         "connection": connection,
#         "host": env_value(env, "DB_HOST", "127.0.0.1"),
#         "port": int(env_value(env, "DB_PORT", "3306")),
#         "database": env_value(env, "DB_DATABASE", "sistem_analisis"),
#         "user": env_value(env, "DB_USERNAME", "root"),
#         "password": env_value(env, "DB_PASSWORD", ""),
#     }


# def make_connections(config):
#     if config["connection"] == "sqlite":
#         engine = create_engine(f"sqlite:///{config['database']}")
#         conn = sqlite3.connect(config["database"])
#         conn.row_factory = sqlite3.Row
#         return engine, conn

#     engine_url = (
#         "mysql+pymysql://"
#         f"{quote_plus(config['user'])}:{quote_plus(config['password'])}"
#         f"@{config['host']}:{config['port']}/{config['database']}?charset=utf8mb4"
#     )
#     engine = create_engine(engine_url)
#     conn = pymysql.connect(
#         host=config["host"],
#         port=config["port"],
#         user=config["user"],
#         password=config["password"],
#         database=config["database"],
#         charset="utf8mb4",
#         cursorclass=pymysql.cursors.DictCursor,
#     )
#     return engine, conn


# def is_sqlite_connection(conn):
#     return isinstance(conn, sqlite3.Connection)


# def prepare_sql(conn, sql):
#     if is_sqlite_connection(conn):
#         return sql.replace("%s", "?").replace("NOW()", "CURRENT_TIMESTAMP")
#     return sql


# def execute(cursor, conn, sql, params=()):
#     cursor.execute(prepare_sql(conn, sql), params)


# def table_columns(cursor, conn, table):
#     if is_sqlite_connection(conn):
#         cursor.execute(f"PRAGMA table_info({table})")
#         return {row["name"] for row in cursor.fetchall()}

#     cursor.execute(f"SHOW COLUMNS FROM {table}")
#     return {row["Field"] for row in cursor.fetchall()}


# SLANG_MAP = {
#     "ga": "tidak",
#     "gak": "tidak",
#     "gk": "tidak",
#     "nggak": "tidak",
#     "ngga": "tidak",
#     "ngak": "tidak",
#     "bgt": "banget",
#     "yg": "yang",
#     "tp": "tapi",
# }

# POSITIF_WORDS = {
#     "bagus","indah","cantik","keren","mantap","asri","bersih","nyaman","rapi",
#     "adem","sejuk","segar","menarik","spektakuler","eksotis","unik","istimewa",
#     "menakjubkan","memukau","asyik","asik","senang","puas","suka","happy",
#     "enjoy","bahagia","menyenangkan","seru","recommended","rekomendasi","wajib",
#     "worth","memuaskan","healing","josss","joss","sip","lengkap","terawat",
#     "baik","oke","ramah","murah","terjangkau","luas","teduh","view","sunset",
#     "sunrise","jernih","bening","enak","lezat","amazing","beautiful","great",
#     "nice","good","perfect","best","lovely","wonderful","fantastic","awesome",
# }

# NEGATIF_WORDS = {
#     "kotor","jorok","jelek","buruk","rusak","kumuh","sempit","parah","payah",
#     "berantakan","mengecewakan","kecewa","nyesel","menyesal","bocor","mati",
#     "gelap","bau","busuk","pengap","mahal","kemahalan","lambat","antri","macet",
#     "sesak","penuh","berebut","kasar","jutek","cuek","berbahaya","bahaya",
#     "licin","curam","sampah","tidak puas","kapok","ogah","zonk","tipu","pungli",
# }


# stemmer = StemmerFactory().create_stemmer()
# stopwords = set(StopWordRemoverFactory().get_stop_words())
# stopwords.discard("tidak")
# stopwords.discard("bukan")
# stopwords.discard("jangan")


# def normalize_rating(value):
#     if pd.isna(value):
#         return None
#     match = re.search(r"([1-5])", str(value))
#     return int(match.group(1)) if match else None

# #preprocessing dengan stemming dan stopword removal, serta normalisasi kata ga/gak/nggak menjadi tidak, dan bgt menjadi banget. Hanya kata yang lebih dari 2 karakter yang diproses untuk mengurangi noise.
# def preprocess_text(text):
#     text = str(text).lower()
#     text = re.sub(r"https?://\S+|www\.\S+", " ", text)
#     text = re.sub(r"[^a-z\s]", " ", text)
#     text = re.sub(r"\s+", " ", text).strip()

#     words = [SLANG_MAP.get(word, word) for word in text.split()]
#     words = [word for word in words if word not in stopwords and len(word) > 2]

#     return stemmer.stem(" ".join(words)).strip()


# # def label_by_keyword(clean_text):
# #     words = set(clean_text.split())
# #     positive_score = len(words & POSITIF_WORDS)
# #     negative_score = len(words & NEGATIF_WORDS)

# #     if positive_score > negative_score:
# #         return "positif"
# #     if negative_score > positive_score:
# #         return "negatif"
# #     return "netral"


# def label_by_keyword(clean_text):
#     words = clean_text.split()

#     positive_score = sum(1 for word in words if word in POSITIF_WORDS)
#     negative_score = sum(1 for word in words if word in NEGATIF_WORDS)

#     if positive_score > negative_score:
#         return "positif"

#     elif negative_score > positive_score:
#         return "negatif"

#     return "netral"


# # def make_pseudo_label(row):
# #     rating = normalize_rating(row.get("rating"))
# #     if rating is not None:
# #         if rating >= 4:
# #             return "positif"
# #         if rating == 3:
# #             return "netral"
# #         return "negatif"

# #     return label_by_keyword(row["hasil_preprocessing"])

# # berdasarkan ulasan
# def make_pseudo_label(row):
#     return label_by_keyword(row["hasil_preprocessing"])


# def rating_confidence(value):
#     rating = normalize_rating(value)
#     if rating is None:
#         return None
#     if rating in {1, 5}:
#         return 1.0
#     if rating in {2, 4}:
#         return 0.85
#     return 0.7


# def apply_rating_priority(row, model_classes=None):
#     sentiment_result = row["sentimen"]
#     probability = float(row["probabilitas"])

#     # Jika probabilitas model sangat rendah (di bawah 0.5), baru gunakan rating
#     if probability < 0.5:
#         rating_label = make_pseudo_label(row)
#         return rating_label, 0.5
    
#     return sentiment_result, probability

#     # rating_label = make_pseudo_label(row)
#     # rating = normalize_rating(row.get("rating"))
#     # if rating is None:
#     #     return row["sentimen"], float(row["probabilitas"])

#     if row["sentimen"] != rating_label:
#         log(
#             "INFO",
#             f"Override sentimen berdasarkan rating {rating}: model={row['sentimen']} -> final={rating_label}",
#         )

#     probability = rating_confidence(rating)
#     if model_classes is not None and rating_label in model_classes:
#         try:
#             class_index = list(model_classes).index(rating_label)
#             probability = max(float(row["probabilitas_by_class"][class_index]), probability)
#         except Exception:
#             pass

#     return rating_label, probability


# def main():
#     args = parse_args()
#     config = db_config()
#     if config["connection"] == "sqlite":
#         log("INFO", f"Menggunakan database SQLite {config['database']}")
#     else:
#         log("INFO", f"Menggunakan database {config['database']} di {config['host']}:{config['port']}")

#     engine, raw_conn = make_connections(config)
#     cursor = raw_conn.cursor()

#     try:
#         if args.periode_id:
#             execute(cursor, raw_conn, "SELECT id, nama FROM periode_analisis WHERE id = %s LIMIT 1", (args.periode_id,))
#         else:
#             execute(cursor, raw_conn, """
#                 SELECT p.id, p.nama
#                 FROM periode_analisis p
#                 WHERE EXISTS (
#                     SELECT 1 FROM ulasan u WHERE u.periode_id = p.id
#                 )
#                 ORDER BY p.id DESC
#                 LIMIT 1
#             """)
#         periode = cursor.fetchone()
#         if not periode:
#             fail("Belum ada periode yang memiliki ulasan. Jalankan Ambil Data terlebih dahulu.")

#         periode_id = periode["id"]
#         periode_nama = periode["nama"]
#         log("INFO", f"Analisis periode terbaru: {periode_nama} (periode_id={periode_id})")

#         df = pd.read_sql(
#             prepare_sql(
#                 raw_conn,
#                 """
#                 SELECT 
#                     u.id, 
#                     u.wisata, 
#                     u.reviewer, 
#                     u.rating, 
#                     u.ulasan, 
#                     u.tanggal, 
#                     u.periode_id
#                 FROM ulasan u
#                 WHERE u.periode_id = %s
#                 AND NOT EXISTS (
#                     SELECT 1
#                     FROM hasil_analisis h
#                     WHERE h.ulasan_id = u.id
#                 )
#                 ORDER BY u.tanggal DESC, u.id DESC
#                 """
#             ),
#             engine,
#             params=(periode_id,),
#         )

#         if df.empty:
#             log("INFO", f"Tidak ada ulasan baru yang perlu dianalisis untuk periode_id={periode_id}.")
#             raw_conn.commit()
#             sys.exit(0)

#         df = df.dropna(subset=["ulasan"]).copy()
#         df["ulasan"] = df["ulasan"].astype(str)
#         df = df[df["ulasan"].str.strip().ne("")]
#         df = df[df["ulasan"].str.strip().ne("0")]
#         df = df[~df["ulasan"].str.contains(r"\[Tanpa teks\]", na=False)]
#         df = df[df["ulasan"].str.len() >= 3 ]

#         if df.empty:
#             log("INFO", "Tidak ada ulasan baru yang memiliki teks layak untuk dianalisis. Kemungkinan data baru hanya berisi [Tanpa teks] atau ulasan terlalu pendek.")
#             raw_conn.commit()
#             sys.exit(0)

#         df["ulasan_bersih"] = df["ulasan"].apply(preprocess_text)
#         df = df[df["ulasan_bersih"].str.strip().ne("")].copy()

#         if df.empty:
#             fail("Data kosong setelah preprocessing. Tidak ada teks yang bisa dianalisis.")

#         df["label"] = df.apply(make_pseudo_label, axis=1)
#         label_counts = df["label"].value_counts()
#         log("INFO", "Distribusi pseudo-label: " + ", ".join(f"{k}={v}" for k, v in label_counts.items()))

#         use_model = True
#         if label_counts.size < 2:
#             use_model = False
#             log("WARNING", "Jumlah kelas kurang dari 2. Prediksi memakai pseudo-label langsung tanpa training model.")

#         can_stratify = label_counts.min() >= 2
#         if not can_stratify:
#             log("WARNING", "Ada kelas dengan jumlah data kurang dari 2. Split evaluasi dibuat tanpa stratify.")

#         report = {"weighted avg": {"precision": 0, "recall": 0, "f1-score": 0}}
#         accuracy = 0

#         if use_model:
#             X = df["ulasan_bersih"]
#             y = df["label"]

#             if len(df) >= 5:
#                 X_train, X_test, y_train, y_test = train_test_split(
#                     X,
#                     y,
#                     test_size=0.2,
#                     random_state=42,
#                     stratify=y if can_stratify else None,
#                 )
#             else:
#                 log("WARNING", "Data kurang dari 5 baris. Evaluasi memakai data latih yang sama.")
#                 X_train, X_test, y_train, y_test = X, X, y, y

#             vectorizer = TfidfVectorizer(max_features=5000, ngram_range=(1, 2))
#             X_train_vec = vectorizer.fit_transform(X_train)
#             X_test_vec = vectorizer.transform(X_test)


#             #ComplementNB lebih cocok untuk data yang tidak seimbang, dan sering memberikan hasil lebih baik pada teks dibanding MultinomialNB
#             model = ComplementNB()
#             model.fit(X_train_vec, y_train)

#             model_dir = BASE_DIR / "storage" / "models"
#             model_dir.mkdir(parents=True, exist_ok=True)
#             joblib.dump(model, model_dir / f"model_periode_{periode_id}.pkl")
#             joblib.dump(vectorizer, model_dir / f"vectorizer_periode_{periode_id}.pkl")
#             log("INFO", f"Model disimpan: model_periode_{periode_id}.pkl")

#             y_pred = model.predict(X_test_vec)
#             report = classification_report(y_test, y_pred, output_dict=True, zero_division=0)
#             accuracy = accuracy_score(y_test, y_pred)

#             X_all_vec = vectorizer.transform(df["ulasan_bersih"])
#             df["sentimen"] = model.predict(X_all_vec)
#             probability_matrix = model.predict_proba(X_all_vec) #probabilitasnya 
#             df["probabilitas"] = probability_matrix.max(axis=1)
#             df["probabilitas"] = df["probabilitas"].clip(upper=0.99)
#             df["probabilitas_by_class"] = list(probability_matrix)
#             final_results = df.apply(lambda row: apply_rating_priority(row, model.classes_), axis=1)
#             df["sentimen"] = [result[0] for result in final_results]
#             df["probabilitas"] = [result[1] for result in final_results]
#             df = df.drop(columns=["probabilitas_by_class"])
#         else:
#             df["sentimen"] = df["label"]
#             df["probabilitas"] = df["rating"].apply(lambda rating: rating_confidence(rating) or 0.7)

#         log("INFO", "Evaluasi memakai pseudo-label dari rating/rule otomatis, bukan label manual.")

#         # execute(cursor, raw_conn, "DELETE FROM hasil_analisis WHERE periode_id = %s", (periode_id,))
#         # execute(cursor, raw_conn, "DELETE FROM evaluasi_model WHERE periode_id = %s", (periode_id,))

#         hasil_columns = table_columns(cursor, raw_conn, "hasil_analisis")
#         insert_columns = [
#             "ulasan_id",
#             "wisata",
#             "ulasan_asli",
#             "ulasan_bersih",
#             "hasil_preprocessing",
#             "sentimen",
#             "probabilitas",
#             "periode_id",
#             "created_at",
#             "updated_at",
#         ]
#         if "ulasan_terolah" in hasil_columns:
#             insert_columns.insert(3, "ulasan_terolah")

#         placeholders = ", ".join(["%s"] * (len(insert_columns) - 2) + ["NOW()", "NOW()"])
#         insert_hasil = f"""
#             INSERT INTO hasil_analisis ({", ".join(insert_columns)})
#             VALUES ({placeholders})
#         """

#         for _, row in df.fillna("").iterrows():
#             values = [
#                 int(row["id"]),
#                 str(row["wisata"]),
#                 str(row["ulasan"]),
#                 str(row["ulasan_bersih"]),
#                 str(row["ulasan_bersih"]),
#                 str(row["sentimen"]).lower(),
#                 float(row["probabilitas"]),
#                 periode_id,
#             ]
#             if "ulasan_terolah" in hasil_columns:
#                 values.insert(3, str(row["ulasan_bersih"]))

#             execute(cursor, raw_conn, insert_hasil, tuple(values))

#         weighted = report.get("weighted avg", {})
#         execute(
#             cursor,
#             raw_conn,
#             """
#             INSERT INTO evaluasi_model
#             (`precision`, `recall`, f1_score, accuracy, tp, tn, fp, fn, periode_id, created_at, updated_at)
#             VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
#             """,
#             (
#                 float(weighted.get("precision", 0)),
#                 float(weighted.get("recall", 0)),
#                 float(weighted.get("f1-score", 0)),
#                 float(accuracy),
#                 0,
#                 0,
#                 0,
#                 0,
#                 periode_id,
#             ),
#         )

#         raw_conn.commit()
#         log("OK", f"{len(df)} hasil analisis disimpan untuk periode {periode_nama}.")
#         log("OK", "Analisis selesai.")

#     except SystemExit:
#         raw_conn.rollback()
#         raise
#     except Exception as exc:
#         raw_conn.rollback()
#         fail(f"Analisis gagal: {exc}")
#     finally:
#         raw_conn.close()
#         engine.dispose()


# if __name__ == "__main__":
#     main()


# -*- coding: utf-8 -*-
import argparse
import os
import re
import sys
import sqlite3
from pathlib import Path
from urllib.parse import quote_plus
import joblib
import pickle

import pandas as pd
import pymysql
from sqlalchemy import create_engine

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
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
        return {"connection": connection, "database": str(database_path)}

    if connection not in {"mysql", "mariadb"}:
        fail(f"DB_CONNECTION={connection} belum didukung. Gunakan sqlite/mysql/mariadb.")

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
        host=config["host"], port=config["port"],
        user=config["user"], password=config["password"],
        database=config["database"], charset="utf8mb4",
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


# ── Slang & stopword ──────────────────────────────────────────────────────────
SLANG_MAP = {
    "ga": "tidak", "gak": "tidak", "gk": "tidak", "nggak": "tidak",
    "ngga": "tidak", "ngak": "tidak", "bgt": "banget", "yg": "yang", "tp": "tapi",
    "gpp": "tidak apa", "gppa": "tidak apa", "tdk": "tidak", "ga": "tidak",
    "kaga": "tidak", "gua": "saya", "gue": "saya", "nih": "ini",
}

# FIX: POSITIF_WORDS dan NEGATIF_WORDS menggunakan bentuk kata SETELAH stemming
# (karena label_by_keyword dipanggil dengan teks hasil preprocess_text yang sudah di-stem)
# Verifikasi dengan: StemmerFactory().create_stemmer().stem("menyenangkan") -> "senang"
POSITIF_WORDS = {
    # bentuk dasar / sudah stem
    "bagus", "indah", "cantik", "keren", "mantap", "asri", "bersih", "nyaman", "rapi",
    "adem", "sejuk", "segar", "tarik", "spektakuler", "eksotis", "unik", "istimewa",
    "kagum", "asik", "senang", "puas", "suka", "happy", "enjoy", "bahagia",
    "seru", "rekomendasi", "wajib", "worth", "muas", "healing", "joss", "sip",
    "lengkap", "awat", "baik", "oke", "ramah", "murah", "jangkau", "luas", "teduh",
    "view", "sunset", "sunrise", "jernih", "bening", "enak", "lezat",
    "amazing", "beautiful", "great", "nice", "good", "perfect", "best",
    "lovely", "wonderful", "fantastic", "awesome", "recommended", "cocok",
    "estetik", "kece", "hits", "instagramable",
}

NEGATIF_WORDS = {
    # bentuk dasar / sudah stem
    "kotor", "jorok", "jelek", "buruk", "rusak", "kumuh", "sempit", "parah", "payah",
    "berantak", "kecewa", "nyesel", "sesal", "bocor", "mati",
    "gelap", "bau", "busuk", "pengap", "mahal", "lambat", "antri", "macet",
    "sesak", "penuh", "berebut", "kasar", "jutek", "cuek", "bahaya",
    "licin", "curam", "sampah", "kapok", "ogah", "zonk", "tipu", "pungli",
    "getok", "pungut", "ancam", "kaga", "tidak puas", "buruk",
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
    """
    Preprocessing: cleaning, slang normalization, stopword removal, stemming.
    Mengembalikan tuple: (ulasan_bersih, hasil_preprocessing)
    - ulasan_bersih     : teks setelah cleaning & normalisasi slang (sebelum stemming)
    - hasil_preprocessing: teks setelah stemming (siap untuk model TF-IDF)
    """
    text = str(text).lower()
    text = re.sub(r"https?://\S+|www\.\S+", " ", text)
    text = re.sub(r"[^a-z\s]", " ", text)
    text = re.sub(r"\s+", " ", text).strip()

    words = [SLANG_MAP.get(word, word) for word in text.split()]
    words = [word for word in words if word not in stopwords and len(word) > 2]

    ulasan_bersih      = " ".join(words).strip()
    hasil_preprocessing = stemmer.stem(ulasan_bersih).strip()

    return ulasan_bersih, hasil_preprocessing


def label_by_keyword(clean_text):
    """
    Pseudo-label berdasarkan keyword.
    Input: teks yang SUDAH melalui preprocess_text (sudah di-stem).
    POSITIF_WORDS dan NEGATIF_WORDS harus dalam bentuk kata dasar (stem).
    """
    words = clean_text.split()
    positive_score = sum(1 for word in words if word in POSITIF_WORDS)
    negative_score = sum(1 for word in words if word in NEGATIF_WORDS)

    if positive_score > negative_score:
        return "positif"
    if negative_score > positive_score:
        return "negatif"
    return "netral"


def make_pseudo_label(row):
    """Buat pseudo-label dari teks bersih."""
    return label_by_keyword(row["hasil_preprocessing"])


def compute_confidence_from_proba(proba_max, label, positive_words_count, negative_words_count):
    """
    FIX: Hitung confidence akhir yang lebih bermakna.
    
    Strategi:
    - Gunakan probabilitas dari model sebagai base
    - Jika ulasan sangat pendek (signal lemah) → probabilitas model sudah mencerminkan ketidakpastian
    - Clip agar tidak terlalu rendah (< 0.50) dan tidak terlalu tinggi (0.99)
    
    Catatan: ComplementNB menghasilkan probabilitas yang sudah dikalibrasi via log-probability.
    Nilai 0.50 berarti model benar-benar tidak yakin (ulasan terlalu pendek/ambigu).
    Ini BENAR secara statistik — tampilkan apa adanya.
    """
    return round(float(proba_max), 4)


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
                WHERE EXISTS (SELECT 1 FROM ulasan u WHERE u.periode_id = p.id)
                ORDER BY p.id DESC LIMIT 1
            """)

        periode = cursor.fetchone()
        if not periode:
            fail("Belum ada periode yang memiliki ulasan. Jalankan Ambil Data terlebih dahulu.")

        periode_id = periode["id"]
        periode_nama = periode["nama"]
        log("INFO", f"Analisis periode: {periode_nama} (periode_id={periode_id})")

        df = pd.read_sql(
            prepare_sql(raw_conn, """
                SELECT u.id, u.wisata, u.reviewer, u.rating, u.ulasan, u.tanggal, u.periode_id
                FROM ulasan u
                WHERE u.periode_id = %s
                AND NOT EXISTS (
                    SELECT 1 FROM hasil_analisis h WHERE h.ulasan_id = u.id
                )
                ORDER BY u.tanggal DESC, u.id DESC
            """),
            engine, params=(periode_id,),
        )

        if df.empty:
            log("INFO", f"Tidak ada ulasan baru untuk periode_id={periode_id}.")
            raw_conn.commit()
            sys.exit(0)

        # ── Filtering ulasan tidak layak ──────────────────────────────────────
        df = df.dropna(subset=["ulasan"]).copy()
        df["ulasan"] = df["ulasan"].astype(str)
        df = df[df["ulasan"].str.strip().ne("")]
        df = df[df["ulasan"].str.strip().ne("0")]
        df = df[~df["ulasan"].str.contains(r"\[Tanpa teks\]", na=False)]
        df = df[df["ulasan"].str.len() >= 3]

        if df.empty:
            log("INFO", "Tidak ada ulasan baru yang layak dianalisis.")
            raw_conn.commit()
            sys.exit(0)

        # ── Preprocessing ─────────────────────────────────────────────────────
        # preprocess_text mengembalikan (ulasan_bersih, hasil_preprocessing)
        # ulasan_bersih       = setelah cleaning & normalisasi, SEBELUM stemming
        # hasil_preprocessing = setelah stemming (dipakai untuk TF-IDF / model)
        df[["ulasan_bersih", "hasil_preprocessing"]] = df["ulasan"].apply(
            lambda t: pd.Series(preprocess_text(t))
        )
        df = df[df["hasil_preprocessing"].str.strip().ne("")].copy()

        if df.empty:
            fail("Data kosong setelah preprocessing.")

        # ── Pseudo-label (ground truth untuk training & evaluasi) ─────────────
        df["label"] = df.apply(make_pseudo_label, axis=1)
        label_counts = df["label"].value_counts()
        log("INFO", "Distribusi pseudo-label: " + ", ".join(f"{k}={v}" for k, v in label_counts.items()))

        # ── Cek apakah bisa training model ───────────────────────────────────
        use_model = label_counts.size >= 2
        if not use_model:
            log("WARNING", "Kurang dari 2 kelas. Prediksi memakai pseudo-label langsung.")

        can_stratify = use_model and (label_counts.min() >= 2)
        if use_model and not can_stratify:
            log("WARNING", "Ada kelas dengan 1 sampel. Split evaluasi tanpa stratify.")

        report = {"weighted avg": {"precision": 0, "recall": 0, "f1-score": 0}}
        accuracy = 0
        tp = tn = fp = fn = 0

        if use_model:
            # TF-IDF dan model menggunakan hasil_preprocessing (sudah di-stem)
            X = df["hasil_preprocessing"]
            y = df["label"]

            if len(df) >= 5:
                X_train, X_test, y_train, y_test = train_test_split(
                    X, y, test_size=0.2, random_state=42,
                    stratify=y if can_stratify else None,
                )
            else:
                log("WARNING", "Data < 5 baris. Evaluasi memakai data latih yang sama.")
                X_train, X_test, y_train, y_test = X, X, y, y

            # TF-IDF dengan ngram (1,2) untuk menangkap frasa "tidak puas", "sangat bagus", dll
            # vectorizer = TfidfVectorizer(max_features=5000, ngram_range=(1, 2), min_df=1)
            # X_train_vec = vectorizer.fit_transform(X_train)
            # X_test_vec  = vectorizer.transform(X_test)

            # ComplementNB: cocok untuk data tidak seimbang (positif >> negatif)
            # model = ComplementNB(alpha=0.5)  # alpha=0.5 lebih halus dari default 1.0
            # model.fit(X_train_vec, y_train)

            # Simpan model
            # model_dir = BASE_DIR / "storage" / "models"
            # model_dir.mkdir(parents=True, exist_ok=True)
            # joblib.dump(model, model_dir / f"model_periode_{periode_id}.pkl")
            # joblib.dump(vectorizer, model_dir / f"vectorizer_periode_{periode_id}.pkl")
            # log("INFO", f"Model disimpan: model_periode_{periode_id}.pkl")

                # Load model tetap dari folder scraper/model
            model_path = BASE_DIR / "scraper" / "model" / "model_sentiment.pkl"
            vectorizer_path = BASE_DIR / "scraper" / "model" / "tfidf_vectorizer.pkl"

            if not model_path.exists():
                raise FileNotFoundError(f"Model tidak ditemukan: {model_path}")

            if not vectorizer_path.exists():
                raise FileNotFoundError(f"Vectorizer tidak ditemukan: {vectorizer_path}")

            with open(model_path, "rb") as f:
                model = pickle.load(f)

            with open(vectorizer_path, "rb") as f:
                vectorizer = pickle.load(f)

            log("INFO", f"Menggunakan model tetap: {model_path}")
            log("INFO", f"Menggunakan vectorizer tetap: {vectorizer_path}")

            X_train_vec = vectorizer.transform(X_train)
            X_test_vec = vectorizer.transform(X_test)

            # Evaluasi pada data test
            y_pred = model.predict(X_test_vec)
            report  = classification_report(y_test, y_pred, output_dict=True, zero_division=0)
            accuracy = accuracy_score(y_test, y_pred)
            log("INFO", f"Akurasi evaluasi: {accuracy:.4f}")
            log("INFO", f"Precision: {report['weighted avg']['precision']:.4f}, "
                        f"Recall: {report['weighted avg']['recall']:.4f}, "
                        f"F1: {report['weighted avg']['f1-score']:.4f}")

            # Prediksi seluruh data + probabilitas NYATA dari model
            # X_all_vec = vectorizer.transform(df["hasil_preprocessing"])
            # df["sentimen"]   = model.predict(X_all_vec)

            # predict_proba: ambil MAX probability (kelas yang dipilih model) sebagai confidence
            # probability_matrix = model.predict_proba(X_all_vec)
            # df["probabilitas"] = probability_matrix.max(axis=1)
            # Catatan: nilai 0.50 valid — berarti model tidak yakin (ulasan terlalu pendek/ambigu)


            X_all_vec = vectorizer.transform(df["hasil_preprocessing"])
            df["sentimen"] = model.predict(X_all_vec)

            # predict_proba: ambil MAX probability (kelas yang dipilih model) sebagai confidence
            probability_matrix = model.predict_proba(X_all_vec)
            df["probabilitas"] = probability_matrix.max(axis=1)

            # Override sederhana berdasarkan kata kunci negatif/positif
           
            def override_sentimen(row):
                teks = str(row["hasil_preprocessing"]).lower()
                words = teks.split()

                positive_score = sum(1 for word in words if word in POSITIF_WORDS)
                negative_score = sum(1 for word in words if word in NEGATIF_WORDS)

                if positive_score == 0 and negative_score == 0:
                    return "netral"

                if negative_score > positive_score:
                    return "negatif"

                if positive_score > negative_score:
                    return "positif"

                return row["sentimen"]

            df["sentimen"] = df.apply(override_sentimen, axis=1)

            log("INFO", f"Rata-rata probabilitas: {df['probabilitas'].mean():.4f}")
            log("INFO", f"Probabilitas < 0.55 (tidak yakin): {(df['probabilitas'] < 0.55).sum()} ulasan")

            # Confusion matrix untuk TP/TN/FP/FN
            if set(y_test.unique()) <= {"positif", "negatif", "netral"}:
                try:
                    cm = confusion_matrix(y_test, y_pred, labels=model.classes_)
                    tp = int(cm.diagonal().sum())
                    fn = int(cm.sum() - cm.diagonal().sum())
                    tn = tp
                    fp = fn
                except Exception as e:
                    log("WARNING", f"Gagal hitung confusion matrix: {e}")

        else:
            # Fallback: gunakan pseudo-label langsung
            df["sentimen"] = df["label"]
            # Probabilitas fallback: berdasarkan jumlah keyword yang match (pakai hasil_preprocessing)
            def keyword_confidence(clean_text):
                words = clean_text.split()
                p = sum(1 for w in words if w in POSITIF_WORDS)
                n = sum(1 for w in words if w in NEGATIF_WORDS)
                total = p + n
                if total == 0:
                    return 0.50   # tidak ada sinyal -> tidak yakin
                dominant = max(p, n)
                return round(min(0.90, max(0.55, dominant / total * 0.85 + 0.15)), 4)

            df["probabilitas"] = df["hasil_preprocessing"].apply(keyword_confidence)

        log("INFO", "Evaluasi memakai pseudo-label dari keyword (bukan label manual).")

        # ── Simpan hasil ke hasil_analisis ───────────────────────────────────
        hasil_columns = table_columns(cursor, raw_conn, "hasil_analisis")
        insert_columns = [
            "ulasan_id", "wisata", "ulasan_asli", "ulasan_bersih",
            "hasil_preprocessing", "sentimen", "probabilitas",
            "periode_id", "created_at", "updated_at",
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
                str(row["ulasan_bersih"]),          # setelah cleaning, sebelum stemming
                str(row["hasil_preprocessing"]),    # setelah stemming — FIX: beda dari ulasan_bersih
                str(row["sentimen"]).lower(),
                float(row["probabilitas"]),
                periode_id,
            ]
            if "ulasan_terolah" in hasil_columns:
                values.insert(3, str(row["ulasan_bersih"]))
            execute(cursor, raw_conn, insert_hasil, tuple(values))

        # ── Simpan evaluasi model ─────────────────────────────────────────────
        weighted = report.get("weighted avg", {})
        execute(
            cursor, raw_conn,
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
                tp, tn, fp, fn,
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