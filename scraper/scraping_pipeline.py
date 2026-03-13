from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import mysql.connector
import time
from datetime import datetime

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
# CHROME
# =============================
options = Options()
options.add_argument("--start-maximized")

driver = webdriver.Chrome(options=options)
wait = WebDriverWait(driver, 15)

# =============================
# DESTINASI
# =============================
destinations = {
    "Pantai Papuma": "Pantai Papuma Jember",
    "Pantai Watu Ulo": "Pantai Watu Ulo Jember",
    "Teluk Love": "Teluk Love Jember",
    "Kebun Teh Gunung Gambir": "Kebun Teh Gunung Gambir Jember"
}

# =============================
# LOOP DESTINASI
# =============================
for nama_wisata, keyword in destinations.items():

    saved = 0
    print("\n====================")
    print("Scraping:", nama_wisata)
    print("====================")

    driver.get("https://www.google.com/maps")
    time.sleep(5)

    # =============================
    # CARI TEMPAT
    # =============================
    try:
        search = driver.find_element(By.ID, "searchboxinput")
    except:
        search = driver.find_element(By.XPATH, "//input[@aria-label='Search Google Maps']")

    search.clear()
    search.send_keys(keyword)

    driver.find_element(By.ID, "searchbox-searchbutton").click()

    time.sleep(6)

    # =============================
    # KLIK HASIL PERTAMA
    # =============================
    try:
        first_result = wait.until(
            EC.element_to_be_clickable((By.XPATH, "(//a[contains(@href,'/maps/place')])[1]"))
        )
        first_result.click()
        time.sleep(5)

    except:
        print("Tempat tidak ditemukan")
        continue

    # =============================
    # BUKA TAB REVIEW
    # =============================
    try:
        review_button = wait.until(
            EC.element_to_be_clickable((By.XPATH, "//button[contains(@aria-label,'review')]"))
        )

        review_button.click()
        time.sleep(5)

    except:
        print("Review button tidak ditemukan")
        continue

    # =============================
    # SCROLL REVIEW
    # =============================
    try:
        scrollable_div = driver.find_element(By.XPATH, "//div[@role='region']")
    except:
        print("Container review tidak ditemukan")
        continue

    last_count = 0
    same_count = 0
    reviews = []

    for i in range(200):

        driver.execute_script(
            "arguments[0].scrollTop = arguments[0].scrollHeight",
            scrollable_div
        )

        time.sleep(2)

        reviews = driver.find_elements(By.XPATH, "//div[@data-review-id]")

        current_count = len(reviews)

        print("Scroll", i, "Review:", current_count)

        if current_count == last_count:
            same_count += 1
        else:
            same_count = 0

        if same_count >= 6:
            print("✔ Semua review sudah termuat")
            break

        last_count = current_count

    # =============================
    # AMBIL DATA REVIEW
    # =============================
    for review in reviews:

        try:

            review_id = review.get_attribute("data-review-id")

            if not review_id:
                continue

            # cek duplikasi
            cursor.execute(
                "SELECT COUNT(*) FROM ulasan WHERE review_id=%s",
                (review_id,)
            )

            exists = cursor.fetchone()[0]

            if exists > 0:
                continue

            # reviewer
            try:
                reviewer = review.find_element(
                    By.XPATH, ".//div[contains(@class,'d4r55')]"
                ).text
            except:
                reviewer = "Unknown"

            # rating
            try:
                rating_text = review.find_element(
                    By.XPATH, ".//span[@role='img']"
                ).get_attribute("aria-label")

                rating = float(rating_text.split()[0])
            except:
                rating = 0

            # ulasan
            try:
                review_text = review.find_element(
                    By.XPATH, ".//span[contains(@class,'wiI7pd')]"
                ).text
            except:
                review_text = ""

            # tanggal
            try:
                review_date = review.find_element(
                    By.XPATH, ".//span[contains(@class,'rsqaWe')]"
                ).text
            except:
                review_date = ""

            if review_text == "":
                continue

            # =============================
            # INSERT DATABASE
            # =============================
            cursor.execute("""
            INSERT INTO ulasan
            (review_id,destinasi,reviewer,ulasan,rating,tanggal,scraping_date)
            VALUES(%s,%s,%s,%s,%s,%s,%s)
            """,
            (
                review_id,
                nama_wisata,
                reviewer,
                review_text,
                rating,
                review_date,
                datetime.now()
            ))

            conn.commit()
            saved += 1

        except Exception as e:
            print("skip:", e)

    print("Total tersimpan:", saved)

print("\n====================")
print("SCRAPING SELESAI")
print("====================")

driver.quit()
conn.close()