import pandas as pd
import mysql.connector
import re
import string
import matplotlib.pyplot as plt
from sklearn.utils import resample


from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.metrics import accuracy_score, classification_report

from wordcloud import WordCloud

# =============================
# DATABASE
# =============================
conn = mysql.connector.connect(
    host="localhost",
    database="sentara",
    user="root",
    password=""
)

query = "SELECT wisata, ulasan, rating FROM ulasan"
df = pd.read_sql(query, conn)

print("Total data:", len(df))

# =============================
# STEMMER & STOPWORD
# =============================
factory = StemmerFactory()
stemmer = factory.create_stemmer()

factory_stop = StopWordRemoverFactory()
stopword = factory_stop.create_stop_word_remover()

# =============================
# PREPROCESSING
# =============================
# kamus slang
slang_dict = {
    "gk": "tidak",
    "ga": "tidak",
    "bgt": "banget",
    "tp": "tapi",
    "dr": "dari",
    "yg": "yang"
}

def normalize_slang(text):
    words = text.split()
    return ' '.join([slang_dict.get(w, w) for w in words])


def preprocess(text):
    text = str(text)
    text = text.lower()

    # cleaning
    text = re.sub(r'[^a-zA-Z\s]', '', text)

    # 🔥 TAMBAHKAN DI SINI
    text = normalize_slang(text)

    # stopword
    text = stopword.remove(text)

    # stemming
    text = stemmer.stem(text)

    return text

def preprocess(text):
    text = str(text)
    text = text.lower()
    text = re.sub(r'[^a-zA-Z\s]', '', text)
    text = stopword.remove(text)
    text = stemmer.stem(text)
    return text

df['clean_text'] = df['ulasan'].apply(preprocess)

# =============================
# LABELING
# =============================
def label_sentimen(rating):
    if rating >= 4:
        return "positif"
    elif rating == 3:
        return "negatif"   # 🔥 ubah ini
    else:
        return "negatif"

df['label'] = df['rating'].apply(label_sentimen)

print("\nDistribusi Label:")
print(df['label'].value_counts())


# =============================
# SPLIT DATA
# =============================
X_train, X_test, y_train, y_test = train_test_split(
    df['clean_text'],
    df['label'],
    test_size=0.2,
    random_state=42
)

# =============================
# TF-IDF
# =============================
vectorizer = TfidfVectorizer()
X_train_tfidf = vectorizer.fit_transform(X_train)
X_test_tfidf = vectorizer.transform(X_test)

# =============================
# MODEL NAIVE BAYES
# =============================
model = MultinomialNB()
model.fit(X_train_tfidf, y_train)

# =============================
# EVALUASI
# =============================
y_pred = model.predict(X_test_tfidf)

print("\nAccuracy:", accuracy_score(y_test, y_pred))
print("\nClassification Report:\n", classification_report(y_test, y_pred))

# =============================
# PREDIKSI SEMUA DATA
# =============================
print("\nMulai prediksi semua data...")

X_all_tfidf = vectorizer.transform(df['clean_text'])
df['prediksi'] = model.predict(X_all_tfidf)

# =============================
# SIMPAN KE DATABASE
# =============================
cursor = conn.cursor()

cursor.execute("DELETE FROM preprocessing_data")
cursor.execute("DELETE FROM hasil_analisis")

for i, row in df.iterrows():
    try:
        # preprocessing
        cursor.execute("""
            INSERT INTO preprocessing_data 
            (wisata, ulasan_asli, cleaning, tokenizing, stemming, final_text)
            VALUES (%s, %s, %s, %s, %s, %s)
        """, (
            row['wisata'],
            row['ulasan'],
            row['clean_text'],
            row['clean_text'],
            row['clean_text'],
            row['clean_text']
        ))

        # hasil analisis
        cursor.execute("""
            INSERT INTO hasil_analisis 
            (wisata, ulasan_terolah, hasil_preprocessing, sentimen, probabilitas)
            VALUES (%s, %s, %s, %s, %s)
        """, (
            row['wisata'],
            row['clean_text'],
            row['clean_text'],
            row['prediksi'],
            0.0
        ))

    except Exception as e:
        print("Error insert:", e)

conn.commit()
print(" Data berhasil disimpan")