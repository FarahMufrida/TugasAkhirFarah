# -*- coding: utf-8 -*-

import re
import sys
import pickle

from pathlib import Path
from urllib.parse import quote_plus

import pandas as pd
import pymysql
from sqlalchemy import create_engine

from Sastrawi.Stemmer.StemmerFactory import StemmerFactory

import nltk
from nltk.corpus import stopwords

# =========================
# UTF-8
# =========================

sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')

# =========================
# NLTK
# =========================

nltk.download('stopwords')

# =========================
# BASE DIRECTORY
# =========================

BASE_DIR = Path(__file__).resolve().parents[1]

# =========================
# LOAD MODEL
# =========================

with open('model/model_sentiment.pkl', 'rb') as file:
    model = pickle.load(file)

with open('model/tfidf_vectorizer.pkl', 'rb') as file:
    tfidf = pickle.load(file)

# =========================
# PREPROCESSING
# =========================

stop_words = set(stopwords.words('indonesian'))

factory = StemmerFactory()
stemmer = factory.create_stemmer()

def preprocess_text(text):

    # handle null
    if text is None:
        return ''

    # ubah string
    text = str(text)

    # lowercase
    text = text.lower()

    # hapus angka
    text = re.sub(r'\d+', '', text)

    # hapus simbol
    text = re.sub(r'[^\w\s]', '', text)

    # hapus spasi berlebih
    text = re.sub(r'\s+', ' ', text).strip()

    # tokenizing
    words = text.split()

    # stopword removal
    words = [word for word in words if word not in stop_words]

    # stemming
    words = [stemmer.stem(word) for word in words]

    return ' '.join(words)

# =========================
# READ LARAVEL .ENV
# =========================

def read_laravel_env():

    env = {}

    env_file = BASE_DIR / '.env'

    if not env_file.exists():
        return env

    for line in env_file.read_text(encoding='utf-8').splitlines():

        line = line.strip()

        if not line:
            continue

        if line.startswith('#'):
            continue

        if '=' not in line:
            continue

        key, value = line.split('=', 1)

        env[key.strip()] = value.strip()

    return env

env = read_laravel_env()

DB_HOST = env.get('DB_HOST', '127.0.0.1')
DB_PORT = env.get('DB_PORT', '3306')
DB_DATABASE = env.get('DB_DATABASE', 'sistem_analisis')
DB_USERNAME = env.get('DB_USERNAME', 'root')
DB_PASSWORD = env.get('DB_PASSWORD', '')

# =========================
# DATABASE CONNECTION
# =========================

engine_url = (
    "mysql+pymysql://"
    f"{quote_plus(DB_USERNAME)}:{quote_plus(DB_PASSWORD)}"
    f"@{DB_HOST}:{DB_PORT}/{DB_DATABASE}?charset=utf8mb4"
)

engine = create_engine(engine_url)

# =========================
# VALIDASI ARGUMENT
# =========================

if len(sys.argv) < 2:

    print("periode_id tidak ditemukan")
    sys.exit()

periode_id = sys.argv[1]

# =========================
# AMBIL DATA ULASAN
# =========================

query = f"""
SELECT
    id,
    wisata,
    ulasan,
    rating,
    tanggal,
    periode_id
FROM ulasan
WHERE periode_id = {periode_id}
AND id NOT IN (
    SELECT ulasan_id
    FROM hasil_analisis
)
"""

df = pd.read_sql(query, engine)

# =========================
# VALIDASI DATA
# =========================

if df.empty:

    print("Tidak ada data baru untuk dianalisis.")
    sys.exit()

# =========================
# FILTER DATA KOTOR
# =========================

clean_rows = []

for _, row in df.iterrows():

    ulasan = str(row['ulasan']).strip()

    # skip kosong
    if ulasan == '':
        continue

    # skip null text
    if ulasan.lower() == '[tanpa teks]':
        continue

    # skip pendek
    if len(ulasan) < 5:
        continue

    # skip angka semua
    if ulasan.isdigit():
        continue

    clean_rows.append(row)

# =========================
# DATAFRAME BERSIH
# =========================

if len(clean_rows) == 0:

    print("Tidak ada data bersih untuk dianalisis.")
    sys.exit()

df = pd.DataFrame(clean_rows)

# =========================
# HAPUS DUPLIKAT
# =========================

df = df.drop_duplicates(subset=['ulasan'])

# =========================
# PREPROCESSING
# =========================

df['ulasan_bersih'] = df['ulasan'].apply(preprocess_text)

# =========================
# HAPUS YANG JADI KOSONG
# =========================

df = df[df['ulasan_bersih'].str.strip() != '']

# =========================
# VALIDASI ULANG
# =========================

if df.empty:

    print("Tidak ada data valid setelah preprocessing.")
    sys.exit()

# =========================
# TF-IDF
# =========================

X = tfidf.transform(df['ulasan_bersih'])

# =========================
# PREDIKSI SENTIMEN
# =========================

df['sentimen'] = model.predict(X)

probability_matrix = model.predict_proba(X)

df['probabilitas'] = probability_matrix.max(axis=1)

# =========================
# SIMPAN KE DATABASE
# =========================

conn = engine.raw_connection()

cursor = conn.cursor()

jumlah_berhasil = 0

for _, row in df.iterrows():

    try:

        sql = """
        INSERT INTO hasil_analisis
        (
            ulasan_id,
            wisata,
            ulasan_asli,
            ulasan_terolah,
            ulasan_bersih,
            hasil_preprocessing,
            sentimen,
            probabilitas,
            periode_id,
            created_at,
            updated_at
        )
        VALUES
        (
            %s,%s,%s,%s,%s,%s,%s,%s,%s,NOW(),NOW()
        )
        """

        values = (
            int(row['id']),
            str(row['wisata']),
            str(row['ulasan']),
            str(row['ulasan_bersih']),
            str(row['ulasan_bersih']),
            str(row['ulasan_bersih']),
            str(row['sentimen']),
            float(row['probabilitas']),
            int(row['periode_id'])
        )

        cursor.execute(sql, values)

        jumlah_berhasil += 1

    except Exception as e:

        print(f"Gagal insert data ID {row['id']} : {e}")

conn.commit()

cursor.close()
conn.close()

# =========================
# OUTPUT
# =========================

print(f"{jumlah_berhasil} data berhasil dianalisis!")