import pandas as pd
import re
import nltk
import pickle

from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from nltk.corpus import stopwords

# Download stopword nltk
nltk.download('stopwords')

# =========================
# LOAD DATASET
# =========================

df = pd.read_excel('dataset/Labeled_LawangSewu_ReviewData.xlsx')

# Ambil kolom penting
df = df[['Text', 'Label']]

# Rename kolom
df.columns = ['review', 'label']

# Lowercase label
df['label'] = df['label'].str.lower()

# Hapus label tidak relevan
df = df[df['label'] != 'tidak relevan']

# Hapus data kosong
df = df.dropna()

# Hapus duplicate
df = df.drop_duplicates(subset=['review'])

# =========================
# PREPROCESSING
# =========================

# Stopword Indonesia
stop_words = set(stopwords.words('indonesian'))

# Stemmer Indonesia
factory = StemmerFactory()
stemmer = factory.create_stemmer()

def preprocess_text(text):

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

# Terapkan preprocessing
df['clean_review'] = df['review'].apply(preprocess_text)

# =========================
# HASIL
# =========================

print(df[['review', 'clean_review', 'label']].head())

from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.metrics import classification_report, accuracy_score

# =========================
# TF-IDF
# =========================

X = df['clean_review']
y = df['label']

tfidf = TfidfVectorizer(
    max_features=5000,
    ngram_range=(1,2),
    min_df=3,
    max_df=0.9,
    sublinear_tf=True
)

X_tfidf = tfidf.fit_transform(X)

# =========================
# SPLIT DATA
# =========================

X_train, X_test, y_train, y_test = train_test_split(
    X_tfidf,
    y,
    test_size=0.2,
    random_state=42,
    stratify=y
)

# =========================
# TRAIN MODEL
# =========================

model = MultinomialNB(fit_prior=False)

model.fit(X_train, y_train)

# =========================
# PREDIKSI
# =========================

y_pred = model.predict(X_test)

# =========================
# EVALUASI
# =========================

accuracy = accuracy_score(y_test, y_pred)

print("\n=== HASIL EVALUASI MODEL ===")
print(f"Akurasi: {accuracy:.4f}")

print("\nClassification Report:")
print(classification_report(y_test, y_pred))

# =========================
# SIMPAN MODEL
# =========================

with open('model/model_sentiment.pkl', 'wb') as file:
    pickle.dump(model, file)

with open('model/tfidf_vectorizer.pkl', 'wb') as file:
    pickle.dump(tfidf, file)

print("\nModel berhasil disimpan!")