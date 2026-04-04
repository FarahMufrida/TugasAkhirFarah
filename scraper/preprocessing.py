import mysql.connector
import re
import math
from collections import Counter
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory

# =============================
# DATABASE
# =============================
conn = mysql.connector.connect(
    host="localhost",
    database="sentara",
    user="root",
    password=""
)

cursor = conn.cursor()

# =============================
# STEMMER
# =============================
factory = StemmerFactory()
stemmer = factory.create_stemmer()

# =============================
# DATA TRAINING
# =============================
data_train = [
    ("bagus bersih indah nyaman sejuk", "positif"),
    ("indah nyaman segar bagus", "positif"),
    ("kotor jelek rusak mahal sempit", "negatif"),
    ("jalan rusak sempit mahal", "negatif")
]

# =============================
# TRAIN NAIVE BAYES
# =============================
def train_nb(data):
    word_counts = {"positif": Counter(), "negatif": Counter()}
    class_counts = {"positif": 0, "negatif": 0}
    total_words = {"positif": 0, "negatif": 0}

    for text, label in data:
        words = text.split()
        class_counts[label] += 1
        word_counts[label].update(words)
        total_words[label] += len(words)

    return word_counts, class_counts, total_words

word_counts, class_counts, total_words = train_nb(data_train)

# =============================
# PREDICT
# =============================
def predict_nb(text):
    words = text.split()

    vocab = set(word_counts["positif"]).union(set(word_counts["negatif"]))

    log_pos = math.log(class_counts["positif"] / sum(class_counts.values()))
    log_neg = math.log(class_counts["negatif"] / sum(class_counts.values()))

    for word in words:
        prob_pos = (word_counts["positif"][word] + 1) / (total_words["positif"] + len(vocab))
        prob_neg = (word_counts["negatif"][word] + 1) / (total_words["negatif"] + len(vocab))

        log_pos += math.log(prob_pos)
        log_neg += math.log(prob_neg)

    if log_pos > log_neg:
        return "Positif", log_pos
    else:
        return "Negatif", log_neg

# =============================
# HAPUS DATA LAMA (PENTING!)
# =============================
cursor.execute("DELETE FROM preprocessing_data")
cursor.execute("DELETE FROM hasil_analisis")

# =============================
# AMBIL DATA
# =============================
cursor.execute("SELECT wisata, ulasan FROM ulasan")
data = cursor.fetchall()

print("Total ulasan:", len(data))

# =============================
# PROSES
# =============================
for row in data:

    wisata = row[0]
    text = row[1]

    if not text:
        continue

    # CLEANING
    text_clean = re.sub(r'[^a-zA-Z\s]', '', text)
    text_clean = text_clean.lower()

    # TOKENIZING 
    tokens = text_clean.split()
    text_token = ' '.join(tokens)

    # STEMMING
    text_stem = stemmer.stem(text_clean)

    # PREDIKSI
    sentimen, probabilitas = predict_nb(text_stem)

    # INSERT PREPROCESSING
    cursor.execute("""
        INSERT INTO preprocessing_data
        (wisata, ulasan_asli, cleaning, tokenizing, stemming)
        VALUES (%s,%s,%s,%s,%s)
        """,
        (
            wisata,
            text,
            text_clean,
            text_token,
            text_stem
        ))

    # INSERT HASIL ANALISIS
    cursor.execute("""
    INSERT INTO hasil_analisis
    (wisata, ulasan_terolah, hasil_preprocessing, sentimen, probabilitas)
    VALUES (%s,%s,%s,%s,%s)
    """, (wisata, text, text_stem, sentimen, probabilitas))

# =============================
# COMMIT SEKALI SAJA
# =============================
conn.commit()

print(" Preprocessing & Analisis Sentimen selesai")

conn.close()