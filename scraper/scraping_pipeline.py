# -*- coding: utf-8 -*-
import argparse
import os
import json
import re
import sys
import time
from datetime import datetime, timedelta
from pathlib import Path
from zoneinfo import ZoneInfo
from urllib import error, parse, request

import pymysql

sys.stdout.reconfigure(encoding="utf-8")
sys.stderr.reconfigure(encoding="utf-8")

BASE_DIR = Path(__file__).resolve().parents[1]


def log(level, message):
    print(f"[{level}] {message}", flush=True)


def fail(message, code=1):
    log("ERROR", message)
    sys.exit(code)


def parse_args():
    parser = argparse.ArgumentParser(description="Scrape ulasan Google Maps untuk rentang tanggal tertentu.")
    parser.add_argument("--start-date", help="Tanggal awal format YYYY-MM-DD. Default: awal bulan berjalan.")
    parser.add_argument("--end-date", help="Tanggal akhir format YYYY-MM-DD. Default: hari ini.")
    parser.add_argument(
        "--wisata",
        default="all",
        help="Nama wisata yang akan diambil. Gunakan 'all' atau kosong untuk semua lokasi.",
    )
    return parser.parse_args()


def parse_date(value, fallback):
    if not value:
        return fallback
    try:
        return datetime.strptime(value, "%Y-%m-%d")
    except ValueError:
        fail(f"Format tanggal tidak valid: {value}. Gunakan YYYY-MM-DD.")


def read_laravel_env():
    env = {}
    env_file = BASE_DIR / ".env"
    if not env_file.exists():
        return env

    for line in env_file.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        env.setdefault(key.strip(), value.strip().strip('"').strip("'"))

    return env


def env_value(env, key, default=""):
    # .env file dibaca DULUAN, baru fallback ke os.getenv, lalu default
    value = env.get(key) or os.getenv(key) or default
    if value in {None, "", "null", "None"}:
        return default
    return value


def env_bool(env, key, default=False):
    value = str(env_value(env, key, str(default))).strip().lower()
    return value in {"1", "true", "yes", "on"}


def db_config():
    env = read_laravel_env()
    connection = env_value(env, "DB_CONNECTION", "mysql")
    if connection not in {"mysql", "mariadb"}:
        fail(f"DB_CONNECTION={connection} belum didukung oleh scraping_pipeline.py. Gunakan mysql/mariadb.")

    return {
        "host": env_value(env, "DB_HOST", "127.0.0.1"),
        "port": int(env_value(env, "DB_PORT", "3306")),
        "database": env_value(env, "DB_DATABASE", "sentara"),
        "user": env_value(env, "DB_USERNAME", "root"),
        "password": env_value(env, "DB_PASSWORD", ""),
    }


def scraper_config():
    env = read_laravel_env()
    return {
        "timezone": env_value(env, "APP_TIMEZONE", "Asia/Jakarta"),
        "provider": env_value(env, "SCRAPER_PROVIDER", "selenium").lower(),
        "headless": env_bool(env, "SCRAPER_HEADLESS", True),
        "user_data_dir": env_value(env, "CHROME_USER_DATA_DIR", ""),
        "profile": env_value(env, "CHROME_PROFILE", ""),
        "scroll_limit": int(env_value(env, "SELENIUM_SCROLL_LIMIT", "20")),
        "manual_login_timeout": int(env_value(env, "SELENIUM_MANUAL_LOGIN_TIMEOUT", "180")),
        "filter_date_range": env_bool(env, "SCRAPER_FILTER_DATE_RANGE", True),
        "require_all_destinations": env_bool(env, "SCRAPER_REQUIRE_ALL_DESTINATIONS", False),
        "apify_token": env_value(env, "APIFY_TOKEN", ""),
        "apify_actor_id": env_value(env, "APIFY_ACTOR_ID", "compass/google-maps-reviews-scraper"),
        "apify_max_reviews": int(env_value(env, "APIFY_MAX_REVIEWS", "100")),
        "apify_language": env_value(env, "APIFY_LANGUAGE", "id"),
        "apify_timeout": int(env_value(env, "APIFY_TIMEOUT_SECONDS", "360")),
    }


def normalize_rating(value):
    if value is None:
        return None
    match = re.search(r"([1-5])", str(value))
    return int(match.group(1)) if match else None


def subtract_months(value, months):
    month_index = value.month - 1 - months
    year = value.year + month_index // 12
    month = month_index % 12 + 1
    days_in_month = [31, 29 if year % 4 == 0 and (year % 100 != 0 or year % 400 == 0) else 28,
                     31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
    day = min(value.day, days_in_month[month - 1])
    return value.replace(year=year, month=month, day=day)


def estimate_review_date(text, now=None):
    if not text:
        return None

    now = now or datetime.now()
    raw = str(text).strip().lower()

    for fmt in ("%Y-%m-%d", "%Y-%m-%d %H:%M:%S", "%d/%m/%Y", "%d-%m-%Y"):
        try:
            return datetime.strptime(raw[:19], fmt)
        except ValueError:
            pass

    if any(word in raw for word in ["baru saja", "hari ini", "sekarang", "just now"]):
        return now
    if "kemarin" in raw or "yesterday" in raw:
        return now - timedelta(days=1)

    match = re.search(r"(\d+|se)\s*(menit|jam|hari|minggu|bulan|tahun|minute|hour|day|week|month|year)", raw)
    if not match:
        return None

    amount = 1 if match.group(1) == "se" else int(match.group(1))
    unit = match.group(2)

    if unit in {"menit", "minute"}:
        return now - timedelta(minutes=amount)
    if unit in {"jam", "hour"}:
        return now - timedelta(hours=amount)
    if unit in {"hari", "day"}:
        return now - timedelta(days=amount)
    if unit in {"minggu", "week"}:
        return now - timedelta(weeks=amount)
    if unit in {"bulan", "month"}:
        return subtract_months(now, amount)
    if unit in {"tahun", "year"}:
        return subtract_months(now, amount * 12)

    return None


def is_current_period_review(tanggal_text, periode_bulan, periode_tahun):
    estimated = estimate_review_date(tanggal_text)
    return estimated is not None and estimated.month == periode_bulan and estimated.year == periode_tahun


def is_review_in_date_range(tanggal_text, start_date, end_date, now=None):
    estimated = estimate_review_date(tanggal_text, now)
    if estimated is None:
        return False
    estimated_date = estimated.date()
    return start_date.date() <= estimated_date <= end_date.date()


def normalized_review_date(tanggal_text):
    estimated = estimate_review_date(tanggal_text)
    return estimated.strftime("%Y-%m-%d") if estimated else str(tanggal_text or "")


def get_or_create_period(cursor, conn, start_date, end_date):
    bulan = start_date.month
    tahun = start_date.year
    nama_bulan = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember",
    ]
    nama = f"{nama_bulan[bulan - 1]} {tahun}"

    cursor.execute(
        "SELECT id, nama FROM periode_analisis WHERE bulan = %s AND tahun = %s LIMIT 1",
        (bulan, tahun),
    )
    periode = cursor.fetchone()
    if periode:
        return periode[0], periode[1]

    cursor.execute(
        """
        INSERT INTO periode_analisis (nama, bulan, tahun, created_at, updated_at)
        VALUES (%s, %s, %s, NOW(), NOW())
        """,
        (nama, bulan, tahun),
    )
    conn.commit()
    return cursor.lastrowid, nama


DESTINATIONS = {
    "Pantai Papuma": "https://www.google.com/maps/search/?api=1&query=Pantai%20Papuma%20Jember",
    "Pantai Watu Ulo": "https://www.google.com/maps/search/?api=1&query=Pantai%20Watu%20Ulo%20Jember",
    "Teluk Love": "https://www.google.com/maps/search/?api=1&query=Teluk%20Love%20Jember",
    "Kebun Teh Gunung Gambir": "https://www.google.com/maps/search/?api=1&query=Kebun%20Teh%20Gunung%20Gambir%20Jember",
}


def selected_destinations(wisata_name):
    if not wisata_name or wisata_name.strip().lower() in {"all", "semua", "semua destinasi", "semua lokasi"}:
        return DESTINATIONS

    normalized = wisata_name.strip().lower()
    for name, url in DESTINATIONS.items():
        if name.lower() == normalized:
            return {name: url}

    fail(
        "Wisata tidak dikenal: "
        + wisata_name
        + ". Pilihan tersedia: all, "
        + ", ".join(DESTINATIONS.keys())
    )


def build_chrome_options(config):
    from selenium.webdriver.chrome.options import Options

    options = Options()
    options.add_argument("--lang=id")
    options.add_argument("--accept-language=id-ID,id")
    options.add_argument("--window-size=1366,900")
    options.add_argument("--disable-blink-features=AutomationControlled")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--no-first-run")
    options.add_argument("--no-default-browser-check")

    if config["headless"]:
        options.add_argument("--headless=new")
    else:
        options.add_argument("--start-maximized")

    if config["user_data_dir"]:
        options.add_argument(f"--user-data-dir={config['user_data_dir']}")

    if config["profile"]:
        options.add_argument(f"--profile-directory={config['profile']}")

    return options


def is_login_or_consent_page(driver):
    current_url = driver.current_url.lower()
    login_url_markers = [
        "accounts.google.com",
        "signin",
    ]
    consent_url_markers = [
        "consent.google.com",
    ]
    return any(marker in current_url for marker in login_url_markers + consent_url_markers)


def wait_for_manual_login(driver, timeout_seconds):
    if not is_login_or_consent_page(driver):
        return True

    log(
        "WARNING",
        f"Google membuka halaman login/consent. Silakan login manual di Chrome. Menunggu maksimal {timeout_seconds} detik.",
    )
    deadline = time.time() + timeout_seconds
    while time.time() < deadline:
        time.sleep(3)
        if not is_login_or_consent_page(driver):
            log("INFO", "Login/consent selesai, scraping dilanjutkan.")
            return True

    return False


def click_possible_consent(driver):
    from selenium.webdriver.common.by import By

    labels = [
        "Terima semua",
        "Saya setuju",
        "Setuju",
        "Accept all",
        "I agree",
    ]
    for label in labels:
        try:
            buttons = driver.find_elements(
                By.XPATH,
                f"//button[contains(., '{label}')] | //div[@role='button'][contains(., '{label}')]",
            )
            for button in buttons:
                if button.is_displayed():
                    driver.execute_script("arguments[0].click();", button)
                    time.sleep(2)
                    log("INFO", f"Tombol consent diklik: {label}")
                    return True
        except Exception:
            continue
    return False


def click_first_search_result_if_needed(driver):
    from selenium.webdriver.common.by import By

    try:
        results = driver.find_elements(By.XPATH, "//a[contains(@href, '/maps/place/')]")
        for result in results:
            if result.is_displayed():
                driver.execute_script("arguments[0].click();", result)
                time.sleep(5)
                log("INFO", "Hasil pencarian Google Maps pertama dibuka.")
                return True
    except Exception as exc:
        log("WARNING", f"Gagal membuka hasil pencarian pertama: {exc}")

    return False


def first_value(data, keys, default=""):
    for key in keys:
        value = data.get(key)
        if value not in {None, ""}:
            return value
    return default


def normalize_actor_id(actor_id):
    return actor_id.strip().replace("/", "~")


def fetch_apify_reviews(wisata, url, config, start_date, end_date):
    if not config["apify_token"]:
        fail("APIFY_TOKEN belum diisi di .env.")

    actor_id = normalize_actor_id(config["apify_actor_id"])
    query = parse.urlencode({"token": config["apify_token"]})
    endpoint = f"https://api.apify.com/v2/acts/{actor_id}/run-sync-get-dataset-items?{query}"
    payload = {
        "startUrls": [{"url": url}],
        "maxReviews": config["apify_max_reviews"],
        "language": config["apify_language"],
    }
    body = json.dumps(payload).encode("utf-8")
    req = request.Request(
        endpoint,
        data=body,
        headers={"Content-Type": "application/json"},
        method="POST",
    )

    try:
        log("INFO", f"Apify: mengambil {wisata} maksimal {config['apify_max_reviews']} review")
        with request.urlopen(req, timeout=config["apify_timeout"]) as response:
            raw = response.read().decode("utf-8")
            data = json.loads(raw) if raw else []
    except error.HTTPError as exc:
        detail = exc.read().decode("utf-8", errors="replace")
        fail(f"Apify gagal HTTP {exc.code}: {detail}")
    except error.URLError as exc:
        fail(f"Apify gagal diakses: {exc}")
    except json.JSONDecodeError as exc:
        fail(f"Response Apify bukan JSON valid: {exc}")

    if not isinstance(data, list):
        fail("Response Apify tidak berbentuk list dataset items.")

    rows = []
    try:
        scrape_now = datetime.now(ZoneInfo(config["timezone"])).replace(tzinfo=None)
    except Exception:
        scrape_now = datetime.now()

    for item in data:
        if not isinstance(item, dict):
            continue

        ulasan = first_value(item, ["text", "reviewText", "textTranslated", "snippet"])
        if not str(ulasan).strip():
            continue

        tanggal_text = str(first_value(item, ["publishedAtDate", "publishAt", "publishedAt", "date"], ""))
        estimated_date = estimate_review_date(tanggal_text, scrape_now)
        if config["filter_date_range"] and not is_review_in_date_range(tanggal_text, start_date, end_date, scrape_now):
            continue

        rows.append(
            {
                "wisata": wisata,
                "reviewer": first_value(item, ["name", "reviewerName", "authorName", "reviewer"], "anonymous"),
                "rating": normalize_rating(first_value(item, ["stars", "rating", "score"], None)),
                "ulasan": str(ulasan).strip(),
                "tanggal": estimated_date.strftime("%Y-%m-%d") if estimated_date else str(tanggal_text or ""),
            }
        )

    return rows


def insert_reviews(cursor, conn, reviews, periode_id):
    saved = 0
    skipped_duplicate = 0

    for review in reviews:
        cursor.execute(
            """
            SELECT id FROM ulasan
            WHERE wisata = %s AND reviewer = %s AND ulasan = %s AND tanggal = %s
            LIMIT 1
            """,
            (review["wisata"], review["reviewer"], review["ulasan"], review["tanggal"]),
        )
        if cursor.fetchone():
            skipped_duplicate += 1
            continue

        cursor.execute(
            """
            INSERT INTO ulasan
            (wisata, reviewer, rating, ulasan, tanggal, scraping_date, periode_id, sentimen, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, NULL, NOW(), NOW())
            """,
            (
                review["wisata"],
                review["reviewer"],
                review["rating"],
                review["ulasan"],
                review["tanggal"],
                datetime.now(),
                periode_id,
            ),
        )
        saved += 1

    conn.commit()
    return saved, skipped_duplicate


def count_reviews_for_wisata(cursor, wisata, periode_id):
    cursor.execute(
        "SELECT COUNT(*) FROM ulasan WHERE wisata = %s AND periode_id = %s",
        (wisata, periode_id),
    )
    result = cursor.fetchone()
    return int(result[0] if result else 0)


def purge_out_of_range_reviews(cursor, conn, periode_id, start_date, end_date):
    cursor.execute("SELECT id, wisata, tanggal FROM ulasan WHERE periode_id = %s", (periode_id,))
    rows = cursor.fetchall()
    deleted = 0

    for row in rows:
        review_id, wisata, tanggal = row
        if not is_review_in_date_range(tanggal, start_date, end_date):
            cursor.execute("DELETE FROM ulasan WHERE id = %s", (review_id,))
            deleted += 1
            log("INFO", f"Hapus ulasan luar rentang dari periode aktif: {wisata} ({tanggal})")

    if deleted:
        conn.commit()
    log("INFO", f"Pembersihan ulasan luar rentang: {deleted} baris dihapus.")


def validate_all_destinations_have_data(cursor, periode_id, destinations, require_all=False):
    missing = []
    for wisata in destinations:
        total = count_reviews_for_wisata(cursor, wisata, periode_id)
        log("INFO", f"Validasi data {wisata}: {total} ulasan pada periode_id={periode_id}")
        if total == 0:
            missing.append(wisata)

    if missing and require_all:
        fail(
            "Scraping belum mengambil semua wisata. Belum ada data untuk: "
            + ", ".join(missing)
            + ". Jalankan Ambil Data lagi atau cek apakah halaman Google Maps tempat tersebut membuka tab ulasan."
        )

    if missing:
        log("WARNING", "Belum ada ulasan periode aktif untuk: " + ", ".join(missing))


def validate_all_destinations_processed(processed, destinations):
    missing = [wisata for wisata in destinations if wisata not in processed]
    if missing:
        fail(
            "Scraper belum berhasil membuka/memproses semua lokasi: "
            + ", ".join(missing)
            + ". Cek URL atau selector Google Maps untuk lokasi tersebut."
        )


def scrape_with_apify(cursor, conn, periode_id, start_date, end_date, config, destinations):
    total_saved = 0
    total_skipped_duplicate = 0
    processed = set()

    for wisata, url in destinations.items():
        reviews = fetch_apify_reviews(wisata, url, config, start_date, end_date)
        saved, skipped_duplicate = insert_reviews(cursor, conn, reviews, periode_id)
        processed.add(wisata)
        total_saved += saved
        total_skipped_duplicate += skipped_duplicate
        log("OK", f"{wisata}: Apify dapat {len(reviews)}, simpan {saved}, duplikat dilewati {skipped_duplicate}")

    log("OK", f"Scraping Apify selesai. Total simpan {total_saved}, duplikat dilewati {total_skipped_duplicate}.")
    validate_all_destinations_processed(processed, destinations)
    validate_all_destinations_have_data(cursor, periode_id, destinations, config["require_all_destinations"])


def click_sort_newest(driver):
    from selenium.webdriver.common.by import By

    try:
        for button in driver.find_elements(By.XPATH, "//button"):
            label = (button.get_attribute("aria-label") or button.text or "").lower()
            if "urutkan" in label or "sort" in label:
                driver.execute_script("arguments[0].click();", button)
                time.sleep(1)
                break

        for option in driver.find_elements(By.XPATH, "//*[contains(text(),'Terbaru') or contains(text(),'Newest')]"):
            if option.is_displayed():
                driver.execute_script("arguments[0].click();", option)
                time.sleep(3)
                log("INFO", "Ulasan diurutkan dari yang terbaru.")
                return True
    except Exception as exc:
        log("WARNING", f"Gagal mengurutkan ulasan terbaru: {exc}")

    return False


def click_reviews_tab(driver, wait, wisata):
    from selenium.webdriver.common.by import By
    from selenium.webdriver.support import expected_conditions as EC

    candidates = [
        "//button[contains(translate(@aria-label, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'ulasan')]",
        "//button[contains(translate(@aria-label, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'review')]",
        "//button[.//*[contains(translate(text(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'ulasan')]]",
        "//button[contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'ulasan')]",
        "//button[contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'review')]",
        "//div[@role='tab'][contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'ulasan')]",
        "//div[@role='tab'][contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'review')]",
    ]

    try:
        wait.until(EC.presence_of_all_elements_located((By.XPATH, "//button | //div[@role='tab']")))
    except Exception:
        pass

    for xpath in candidates:
        try:
            elements = driver.find_elements(By.XPATH, xpath)
            for element in elements:
                if element.is_displayed():
                    label = element.get_attribute("aria-label") or element.text or xpath
                    driver.execute_script("arguments[0].click();", element)
                    time.sleep(4)
                    log("INFO", f"{wisata}: tab ulasan dibuka lewat {label}")
                    return True
        except Exception:
            continue

    return False


def find_reviews_scroll_container(driver, wait):
    from selenium.webdriver.common.by import By
    from selenium.webdriver.support import expected_conditions as EC

    xpaths = [
        "//div[@role='region']",
        "//div[contains(@aria-label, 'Ulasan')]",
        "//div[contains(@aria-label, 'Reviews')]",
        "//div[.//div[@data-review-id]]",
    ]

    for xpath in xpaths:
        try:
            return wait.until(EC.presence_of_element_located((By.XPATH, xpath)))
        except Exception:
            continue

    return None


def scrape_with_selenium(cursor, conn, periode_id, start_date, end_date, config, destinations):
    from selenium import webdriver
    from selenium.webdriver.common.by import By
    from selenium.webdriver.support import expected_conditions as EC
    from selenium.webdriver.support.ui import WebDriverWait

    driver = None

    log(
        "INFO",
        "Chrome mode: "
        + ("headless" if config["headless"] else "visible")
        + (f", profile={config['profile']}" if config["profile"] else ""),
    )
    driver = webdriver.Chrome(options=build_chrome_options(config))

    total_saved = 0
    total_skipped_duplicate = 0
    try:
        scrape_now = datetime.now(ZoneInfo(config["timezone"])).replace(tzinfo=None)
    except Exception:
        scrape_now = datetime.now()
    processed = set()

    try:
        for wisata, url in destinations.items():
            log("INFO", f"Scraping: {wisata}")
            driver.get(url)
            wait = WebDriverWait(driver, 25)
            time.sleep(5)
            click_possible_consent(driver)
            click_first_search_result_if_needed(driver)

            if not wait_for_manual_login(driver, config["manual_login_timeout"]):
                fail(
                    "Google Maps masih berada di halaman login/consent setelah waktu tunggu habis. "
                    "Login manual di Chrome profile khusus, lalu klik Ambil Data lagi."
                )

            if not click_reviews_tab(driver, wait, wisata):
                log("WARNING", f"Tombol/tab ulasan tidak ditemukan untuk {wisata}")
                continue

            click_sort_newest(driver)

            scrollable_div = find_reviews_scroll_container(driver, wait)
            if scrollable_div is None:
                log("WARNING", f"Container ulasan tidak ditemukan untuk {wisata}")
                continue

            last_height = 0
            for i in range(config["scroll_limit"]):
                driver.execute_script("arguments[0].scrollTop = arguments[0].scrollHeight", scrollable_div)
                time.sleep(2)
                new_height = driver.execute_script("return arguments[0].scrollHeight", scrollable_div)
                log("INFO", f"{wisata}: scroll ke-{i + 1}")
                if new_height == last_height:
                    break
                last_height = new_height

            for button in driver.find_elements(By.XPATH, "//button[contains(text(),'Selengkapnya')]"):
                try:
                    driver.execute_script("arguments[0].click();", button)
                except Exception:
                    pass

            time.sleep(2)
            items = []
            reviews = driver.find_elements(By.XPATH, "//div[@data-review-id]")
            log("INFO", f"{wisata}: {len(reviews)} review ditemukan")

            for review in reviews:
                try:
                    ulasan = review.find_element(By.XPATH, ".//span[contains(@class,'wiI7pd')]").text.strip()
                    if not ulasan:
                        continue
                    tanggal_text = review.find_element(By.XPATH, ".//span[contains(@class,'rsqaWe')]").text.strip()
                    estimated_date = estimate_review_date(tanggal_text, scrape_now)
                    if config["filter_date_range"] and not is_review_in_date_range(tanggal_text, start_date, end_date, scrape_now):
                        log("INFO", f"{wisata}: skip ulasan luar rentang ({tanggal_text})")
                        continue
                    items.append(
                        {
                            "wisata": wisata,
                            "reviewer": review.find_element(By.XPATH, ".//div[contains(@class,'d4r55')]").text.strip(),
                            "rating": normalize_rating(
                                review.find_element(By.XPATH, ".//span[@role='img']").get_attribute("aria-label")
                            ),
                            "ulasan": ulasan,
                            "tanggal": estimated_date.strftime("%Y-%m-%d") if estimated_date else str(tanggal_text or ""),
                        }
                    )
                except Exception as exc:
                    log("WARNING", f"{wisata}: skip review karena {exc}")

            saved, skipped_duplicate = insert_reviews(cursor, conn, items, periode_id)
            processed.add(wisata)
            total_saved += saved
            total_skipped_duplicate += skipped_duplicate
            log("OK", f"{wisata}: simpan {saved}, duplikat dilewati {skipped_duplicate}")

        log("OK", f"Scraping Selenium selesai. Total simpan {total_saved}, duplikat dilewati {total_skipped_duplicate}.")
        validate_all_destinations_processed(processed, destinations)
        validate_all_destinations_have_data(cursor, periode_id, destinations, config["require_all_destinations"])
    finally:
        driver.quit()


def main():
    args = parse_args()
    now = datetime.now()
    default_start = now.replace(day=1)
    default_end = now
    start_date = parse_date(args.start_date, default_start)
    end_date = parse_date(args.end_date, default_end)
    if end_date.date() < start_date.date():
        fail("end-date tidak boleh lebih kecil dari start-date.")
    if start_date.strftime("%Y-%m") != end_date.strftime("%Y-%m"):
        fail("Rentang tanggal harus berada dalam bulan yang sama karena periode_analisis disimpan per bulan.")

    config = db_config()
    scraper = scraper_config()
    destinations = selected_destinations(args.wisata)
    conn = pymysql.connect(
    host='localhost',
    port=3306,
    user='root',
    password='',  # isi password kalau ada
    database='sistem_analisis',
)
    cursor = conn.cursor()

    try:
        periode_id, periode_nama = get_or_create_period(cursor, conn, start_date, end_date)
        log("INFO", f"Scraping untuk periode {periode_nama} (periode_id={periode_id})")
        log("INFO", f"Provider scraping: {scraper['provider']}")
        log("INFO", "Lokasi scraping: " + ", ".join(destinations.keys()))
        if scraper["filter_date_range"]:
            log("INFO", f"Filter aktif: ulasan {start_date.strftime('%Y-%m-%d')} sampai {end_date.strftime('%Y-%m-%d')}")
            purge_out_of_range_reviews(cursor, conn, periode_id, start_date, end_date)

        if scraper["provider"] == "apify":
            scrape_with_apify(cursor, conn, periode_id, start_date, end_date, scraper, destinations)
        elif scraper["provider"] == "selenium":
            scrape_with_selenium(cursor, conn, periode_id, start_date, end_date, scraper, destinations)
        else:
            fail("SCRAPER_PROVIDER harus bernilai apify atau selenium.")

    except Exception as exc:
        conn.rollback()
        fail(f"Scraping gagal: {exc}")
    finally:
        conn.close()


if __name__ == "__main__":
    main()