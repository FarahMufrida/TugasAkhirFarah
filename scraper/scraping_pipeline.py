from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
import mysql.connector
import time
from datetime import datetime

# DATABASE
conn = mysql.connector.connect(
    host="localhost",
    database="sentara",
    user="root",
    password=""
)
cursor = conn.cursor()

# CHROME
options = Options()
options.add_argument("--start-maximized")
options.add_argument('--lang=id')
Options.add_argument("accept-language-id-ID,id")
driver = webdriver.Chrome(options=options)

destinations = {
    "Pantai Papuma":"https://www.google.com/maps/place/Pantai+Papuma+Jember",
    "Pantai Watu Ulo":"https://www.google.com/maps/place/Pantai+Watu+Ulo+Jember",
    "Teluk Love":"https://www.google.com/maps/place/Teluk+Love+Jember",
    "Kebun Teh Gunung Gambir":"https://www.google.com/maps/place/Kebun+Teh+Gunung+Gambir"
}

for wisata, url in destinations.items():

    print("\nScraping:", wisata)

    driver.get(url)
    time.sleep(10)

    # =========================
    # BUKA ULASAN (FIX)
    # =========================
    try:
        buttons = driver.find_elements(By.XPATH, "//button")

        ketemu = False

        for btn in buttons:
            try:
                label = btn.get_attribute("aria-label")

                if label and ("ulasan" in label.lower() or "review" in label.lower()):
                    driver.execute_script("arguments[0].click();", btn)
                    time.sleep(5)
                    print("Berhasil buka ulasan")
                    ketemu = True
                    break
            except:
                pass

        if not ketemu:
            print("Tombol ulasan tidak ditemukan")
            continue

    except Exception as e:
        print("Error buka ulasan:", e)
        continue

    # =========================
    # SCROLL
    # =========================
    try:
        scrollable_div = driver.find_element(By.XPATH, "//div[@role='region']")
    except:
        print("Container tidak ditemukan")
        continue

    last_height = 0

    for i in range(50):
        driver.execute_script(
            "arguments[0].scrollTop = arguments[0].scrollHeight",
            scrollable_div
        )
        time.sleep(2)

        new_height = driver.execute_script(
            "return arguments[0].scrollHeight",
            scrollable_div
        )

        print("Scroll ke-", i)

        if new_height == last_height:
            print("Sudah mentok")
            break

        last_height = new_height

    # =========================
    # EXPAND
    # =========================
    buttons = driver.find_elements(By.XPATH, "//button[contains(text(),'Selengkapnya')]")
    for b in buttons:
        try:
            driver.execute_script("arguments[0].click();", b)
        except:
            pass

    time.sleep(2)

    # =========================
    # AMBIL DATA
    # =========================
    reviews = driver.find_elements(By.XPATH, "//div[@data-review-id]")

    print("Total review ditemukan:", len(reviews))

    saved = 0

    for review in reviews:
        try:
            reviewer = review.find_element(By.XPATH, ".//div[contains(@class,'d4r55')]").text

            rating = review.find_element(By.XPATH, ".//span[@role='img']").get_attribute("aria-label")

            ulasan = review.find_element(By.XPATH, ".//span[contains(@class,'wiI7pd')]").text

            tanggal = review.find_element(By.XPATH, ".//span[contains(@class,'rsqaWe')]").text

            if ulasan.strip() == "":
                continue

            cursor.execute("""
            INSERT INTO ulasan
            (destinasi, reviewer, ulasan, rating, tanggal, scraping_date)
            VALUES (%s,%s,%s,%s,%s,%s)
            """, (
                wisata,
                reviewer,
                ulasan,
                rating,
                tanggal,
                datetime.now()
            ))

            conn.commit()
            saved += 1

        except Exception as e:
            print("skip:", e)

    print("Berhasil simpan:", saved)

driver.quit()
conn.close()

print("\nSCRAPING SELESAI")