# -*- coding: utf-8 -*-
import sys
sys.stdout.reconfigure(encoding='utf-8')

import re
import numpy as np
import pandas as pd
import pymysql
from sqlalchemy import create_engine

from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import ComplementNB
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix

from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory


# ===============================
# KONEKSI DB
# ===============================
engine = create_engine("mysql+pymysql://root:@localhost/sentara")

raw_conn = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='sentara',
    cursorclass=pymysql.cursors.DictCursor
)
cursor = raw_conn.cursor()

# ===============================
# 1. AMBIL PERIODE AKTIF (TERBARU)
# ===============================
cursor.execute("SELECT id, nama FROM periode_analisis ORDER BY id DESC LIMIT 1")
periode = cursor.fetchone()
periode_id = periode['id']
periode_nama = periode['nama']
print(f"[INFO] Analisis periode: {periode_nama} (id={periode_id})")

# ===============================
# 2. AMBIL DATA PERIODE INI SAJA
# ===============================
df = pd.read_sql(f"SELECT id, wisata, ulasan FROM ulasan WHERE periode_id = {periode_id}", engine)

# ===============================
# 3. CLEANING
# ===============================
df = df.dropna(subset=["ulasan"])
df["ulasan"] = df["ulasan"].astype(str)
df = df[df["ulasan"].str.strip() != ""]
df = df[df["ulasan"].str.strip() != "0"]

# ===============================
# 4. PREPROCESSING
# ===============================
stemmer = StemmerFactory().create_stemmer()
stop_factory = StopWordRemoverFactory()
stopwords = set(stop_factory.get_stop_words())

def clean_text(text):
    text = text.lower()
    text = re.sub(r"http\S+", " ", text)
    text = re.sub(r"[^a-zA-Z\s]", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    words = [w for w in text.split() if w not in stopwords and len(w) > 2]
    return stemmer.stem(" ".join(words))

df["clean"] = df["ulasan"].apply(clean_text)
df = df[df["clean"].str.strip() != ""]

# ===============================
# 5. LABEL (RULE BASED)
# ===============================
positif_words = {
    "bagus", "indah", "mantap", "keren", "cantik", "menarik", "nyaman",
    "bersih", "recommended", "suka", "senang", "puas", "murah", "asyik",
    "ramah", "worth", "spektakuler", "memukau", "sejuk", "baguss", "kece",
    "amazing", "beautiful", "good", "nice", "best", "great", "perfect",
    "recommend", "memuaskan", "menyenangkan", "view"
}
negatif_words = {
    "tidak", "buruk", "mahal", "jelek", "kotor", "kecewa", "rusak",
    "sempit", "panas", "bau", "berbahaya", "sepi", "bosan", "mengecewakan",
    "payah", "parah", "jorok", "macet", "antri", "penuh", "sampah",
    "sayang", "kurang", "susah", "sulit", "jauh", "capek", "lelah"
}

def label_rule(text):
    words = set(text.split())
    skor_pos = len(words & positif_words)
    skor_neg = len(words & negatif_words)
    if skor_pos > skor_neg:
        return "positif"
    elif skor_neg > skor_pos:
        return "negatif"
    else:
        return "netral"

df["label"] = df["clean"].apply(label_rule)

# ===============================
# 6. TF-IDF + MODEL
# ===============================
X = df["clean"]
y = df["label"]

if len(df) > 5:
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )
else:
    X_train, X_test, y_train, y_test = X, X, y, y

vectorizer = TfidfVectorizer(max_features=5000, ngram_range=(1,2))
X_train_vec = vectorizer.fit_transform(X_train)
X_test_vec = vectorizer.transform(X_test)

model = ComplementNB()
model.fit(X_train_vec, y_train)

# ===============================
# 7. EVALUASI
# ===============================
y_pred = model.predict(X_test_vec)

acc = accuracy_score(y_test, y_pred)
report = classification_report(y_test, y_pred, output_dict=True, zero_division=0)
cm = confusion_matrix(y_test, y_pred, labels=["negatif","netral","positif"])

# ===============================
# 8. PREDIKSI SEMUA DATA
# ===============================
X_all = vectorizer.transform(df["clean"])
df["prediksi"] = model.predict(X_all)

# ===============================
# 9. SIMPAN KE DB
# ===============================
df = df.fillna("")

# Hapus data periode ini saja (bukan semua)
cursor.execute("DELETE FROM hasil_analisis WHERE periode_id = %s", (periode_id,))
cursor.execute("DELETE FROM evaluasi_model WHERE periode_id = %s", (periode_id,))

insert_query = """
INSERT INTO hasil_analisis 
(wisata, ulasan_asli, ulasan_bersih, hasil_preprocessing, sentimen, probabilitas, periode_id)
VALUES (%s, %s, %s, %s, %s, %s, %s)
"""

for _, row in df.iterrows():
    cursor.execute(insert_query, (
        str(row["wisata"]),
        str(row["ulasan"]),
        str(row["clean"]),
        str(row["clean"]),
        str(row["prediksi"]),
        float(0.9),
        periode_id
    ))

print(f"[OK] {len(df)} ulasan berhasil disimpan untuk periode {periode_nama}")

cursor.execute("""
INSERT INTO evaluasi_model 
(`precision`, `recall`, f1_score, accuracy, tp, tn, fp, fn, periode_id)
VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
""", (
    float(report["weighted avg"]["precision"]),
    float(report["weighted avg"]["recall"]),
    float(report["weighted avg"]["f1-score"]),
    float(acc),
    int(cm[2][2]) if cm.shape == (3,3) else 0,
    int(cm[0][0]) if cm.shape == (3,3) else 0,
    int(cm[0][2]) if cm.shape == (3,3) else 0,
    int(cm[2][0]) if cm.shape == (3,3) else 0,
    periode_id
))

raw_conn.commit()
raw_conn.close()

print("Analisis selesai [OK]")