import re
import pandas as pd
import pymysql

from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import ComplementNB
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix

from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

# ===============================
# 🔌 KONEKSI DB
# ===============================
conn = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='sentara',
    cursorclass=pymysql.cursors.DictCursor
)

cursor = conn.cursor()

# ===============================
# 1. AMBIL DATA
# ===============================
df = pd.read_sql("SELECT id, wisata, ulasan FROM ulasan", conn)

# ===============================
# 2. CLEANING
# ===============================
df = df.dropna(subset=["ulasan"])
df["ulasan"] = df["ulasan"].astype(str)
df = df[df["ulasan"].str.strip() != ""]

# ===============================
# 3. PREPROCESSING
# ===============================
stemmer = StemmerFactory().create_stemmer()
stop_factory = StopWordRemoverFactory()
stopwords = set(stop_factory.get_stop_words())

def clean_text(text):
    text = text.lower()
    text = re.sub(r"http\S+", " ", text)
    text = re.sub(r"[^a-zA-Z\s]", " ", text)
    text = re.sub(r"\s+", " ", text)

    words = [w for w in text.split() if w not in stopwords]
    return stemmer.stem(" ".join(words))

df["clean"] = df["ulasan"].apply(clean_text)

# ===============================
# 4. LABEL SEMENTARA (dummy)
# ===============================
def label_rule(text):
    if "tidak" in text or "buruk" in text:
        return "negatif"
    elif "bagus" in text or "indah" in text:
        return "positif"
    else:
        return "netral"

df["label"] = df["clean"].apply(label_rule)

# ===============================
# 5. TF-IDF + MODEL
# ===============================
X = df["clean"]
y = df["label"]

X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42, stratify=y
)

vectorizer = TfidfVectorizer()
X_train_vec = vectorizer.fit_transform(X_train)
X_test_vec = vectorizer.transform(X_test)

model = ComplementNB()
model.fit(X_train_vec, y_train)

# ===============================
# 6. EVALUASI
# ===============================
y_pred = model.predict(X_test_vec)

acc = accuracy_score(y_test, y_pred)
report = classification_report(y_test, y_pred, output_dict=True)
cm = confusion_matrix(y_test, y_pred, labels=["negatif","netral","positif"])

# ===============================
# 7. PREDIKSI SEMUA DATA
# ===============================
X_all = vectorizer.transform(df["clean"])
df["prediksi"] = model.predict(X_all)

# ===============================
# 8. SIMPAN KE DB
# ===============================

# kosongkan tabel
cursor.execute("DELETE FROM hasil_analisis")
cursor.execute("DELETE FROM evaluasi_model")

# simpan hasil per ulasan
for _, row in df.iterrows():
    cursor.execute("""
        INSERT INTO hasil_analisis 
        (wisata, ulasan_terolah, hasil_preprocessing, sentimen, probabilitas, created_at)
        VALUES (%s,%s,%s,%s,%s,NOW())
    """, (
        row["wisata"],
        row["ulasan"],
        row["clean"],
        row["prediksi"],
        0.9
    ))

# simpan evaluasi
cursor.execute("""
INSERT INTO evaluasi_model 
(precision, recall, f1_score, accuracy, tp, tn, fp, fn, created_at)
VALUES (%s,%s,%s,%s,%s,%s,%s,%s,NOW())
""", (
    report["weighted avg"]["precision"],
    report["weighted avg"]["recall"],
    report["weighted avg"]["f1-score"],
    acc,
    int(cm[2][2]),
    int(cm[0][0]),
    int(cm[0][2]),
    int(cm[2][0])
))

conn.commit()
conn.close()

print("Analisis selesai ✔")