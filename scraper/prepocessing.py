import mysql.connector
import re
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
# AMBIL DATA ULASAN
# =============================
cursor.execute("SELECT id, ulasan FROM ulasan")

data = cursor.fetchall()

print("Total ulasan:", len(data))

for row in data:

    id_ulasan = row[0]
    text = row[1]

    # =============================
    # CLEANING
    # =============================
    text_clean = re.sub(r'[^a-zA-Z\s]', '', text)

    # =============================
    # CASE FOLDING
    # =============================
    text_clean = text_clean.lower()

    # =============================
    # STEMMING
    # =============================
    text_stem = stemmer.stem(text_clean)

    # =============================
    # INSERT KE DATABASE
    # =============================
    cursor.execute("""
    INSERT INTO preprocessing_data
    (ulasan_asli, cleaning, stemming)
    VALUES (%s,%s,%s)
    """,
    (
        text,
        text_clean,
        text_stem
    ))

    conn.commit()

print("Preprocessing selesai")

conn.close()