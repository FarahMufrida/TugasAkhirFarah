from flask import Flask, request, jsonify
import pickle
import re
import subprocess

import nltk
from nltk.corpus import stopwords
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory

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

nltk.download('stopwords')

stop_words = set(stopwords.words('indonesian'))

factory = StemmerFactory()
stemmer = factory.create_stemmer()

def preprocess_text(text):

    text = str(text).lower()

    # normalisasi huruf berulang
    text = re.sub(r'(.)\1+', r'\1', text)

    # hapus angka
    text = re.sub(r'\d+', '', text)

    # hapus simbol
    text = re.sub(r'[^\w\s]', '', text)

    # hapus spasi berlebih
    text = re.sub(r'\s+', ' ', text).strip()

    # tokenizing
    words = text.split()

    # slang normalization
    slang_dict = {
        'gk': 'tidak',
        'ga': 'tidak',
        'nggak': 'tidak',
        'bgt': 'banget',
        'bgtt': 'banget',
        'bangett': 'banget',
        'bgus': 'bagus',
        'baguss': 'bagus',
        'tmpt': 'tempat',
        'jln': 'jalan',
        'sy': 'saya',
        'yg': 'yang',
        'udh': 'sudah'
    }

    words = [
        slang_dict[word] if word in slang_dict else word
        for word in words
    ]

    # stopword removal
    words = [
        word for word in words
        if word not in stop_words
    ]

    # stemming
    words = [stemmer.stem(word) for word in words]

    return ' '.join(words)

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
# FLASK APP
# =========================

app = Flask(__name__)
@app.route('/scraping', methods=['POST'])
def scraping():

    try:

        data = request.get_json()

        wisata = data.get('wisata', '')

        result = subprocess.run(
            ['python', 'scraping_pipeline.py', wisata],
            capture_output=True,
            text=True
        )

        if result.returncode != 0:

            return jsonify({
                'status': 'error',
                'error': result.stderr
            }), 500

        return jsonify({
            'status': 'success',
            'message': result.stdout
        })

    except Exception as e:

        return jsonify({
            'status': 'error',
            'error': str(e)
        }), 500

    try:

        result = subprocess.run(
            ['python', 'scraping_pipeline.py'],
            capture_output=True,
            text=True
        )

        if result.returncode != 0:

            return jsonify({
                'status': 'error',
                'error': result.stderr
            }), 500

        return jsonify({
            'status': 'success',
            'message': result.stdout
        })

    except Exception as e:

        return jsonify({
            'status': 'error',
            'error': str(e)
        }), 500

# ======================================
# PREDICT 1 REVIEW
# ======================================

@app.route('/predict', methods=['POST'])
def predict():

    data = request.get_json()

    review = data['review']

    # preprocessing
    clean_review = preprocess_text(review)

    # tfidf transform
    vector = tfidf.transform([clean_review])

    # prediksi
    prediction = model.predict(vector)[0]

    # probabilitas
    probability = model.predict_proba(vector).max()

    return jsonify({
        'review': review,
        'clean_review': clean_review,
        'sentiment': prediction,
        'probability': round(float(probability), 4)
    })

# ======================================
# PROSES ANALISIS SEMUA DATA
# ======================================

@app.route('/proses-analisis', methods=['POST'])
def proses_analisis():

    try:

        data = request.get_json()

        periode_id = str(data.get('periode_id'))

        result = subprocess.run(
            ['python', 'analisis.py', periode_id],
            capture_output=True,
            text=True
        )

        if result.returncode != 0:

            return jsonify({
                'status': 'error',
                'error': result.stderr
            }), 500

        return jsonify({
            'status': 'success',
            'message': result.stdout
        })

    except Exception as e:

        return jsonify({
            'status': 'error',
            'error': str(e)
        }), 500

    try:

        result = subprocess.run(
            ['python', 'analisis.py'],
            capture_output=True,
            text=True
        )

        if result.returncode != 0:

            return jsonify({
                'status': 'error',
                'error': result.stderr
            }), 500

        return jsonify({
            'status': 'success',
            'message': result.stdout
        })

    except Exception as e:

        return jsonify({
            'status': 'error',
            'error': str(e)
        }), 500

# =========================
# RUN APP
# =========================

if __name__ == '__main__':
    app.run(debug=False)