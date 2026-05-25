# -*- coding: utf-8 -*-
import argparse
import os
import tempfile

from networkx import config
import undetected_chromedriver as uc

from jmespath import Options
from pandas import options
from selenium import webdriver

if 'PATH' not in os.environ:
    os.environ['PATH'] = r"C:\Windows\System32;C:\Windows"


import json
import re
import sys
import time
import sqlite3
from datetime import datetime, timedelta
from pathlib import Path
from zoneinfo import ZoneInfo
from urllib import error, parse, request


# import pymysql
import mysql.connector

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
    if connection == "sqlite":
        database = env_value(env, "DB_DATABASE", str(BASE_DIR / "database" / "database.sqlite"))
        database_path = Path(database)
        if not database_path.is_absolute():
            database_path = BASE_DIR / database

        return {
            "connection": connection,
            "database": str(database_path),
        }

    if connection not in {"mysql", "mariadb"}:
        fail(f"DB_CONNECTION={connection} belum didukung oleh scraping_pipeline.py. Gunakan sqlite/mysql/mariadb.")

    return {
        "connection": connection,
        "host": env_value(env, "DB_HOST", "127.0.0.1"),
        "port": int(env_value(env, "DB_PORT", "3306")),
        "database": env_value(env, "DB_DATABASE", "sistem_analisis"),
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


def is_sqlite_connection(conn):
    return isinstance(conn, sqlite3.Connection)


def prepare_sql(conn, sql):
    if is_sqlite_connection(conn):
        return sql.replace("%s", "?").replace("NOW()", "CURRENT_TIMESTAMP")
    return sql


def execute(cursor, conn, sql, params=()):
    cursor.execute(prepare_sql(conn, sql), params)


def make_connection(config):
    if config["connection"] == "sqlite":
        return sqlite3.connect(config["database"])

    return mysql.connector.connect(
        host=config["host"],
        port=config["port"],
        user=config["user"],
        password=config["password"],
        database=config["database"],
        charset="utf8mb4",
    )


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

    execute(
        cursor,
        conn,
        "SELECT id, nama FROM periode_analisis WHERE bulan = %s AND tahun = %s LIMIT 1",
        (bulan, tahun),
    )
    periode = cursor.fetchone()
    if periode:
        return periode[0], periode[1]

    execute(
        cursor,
        conn,
        """
        INSERT INTO periode_analisis (nama, bulan, tahun, created_at, updated_at)
        VALUES (%s, %s, %s, NOW(), NOW())
        """,
        (nama, bulan, tahun),
    )
    conn.commit()
    return cursor.lastrowid, nama


DESTINATIONS = {
    "Pantai Papuma": "https://www.google.com/maps/place/Pantai+Papuma/@-8.4310054,113.5508204,16z/data=!4m7!3m6!1s0x2dd682a6a4b5cd8d:0xb9c242f3a09e2d2e!4b1!8m2!3d-8.4300871!4d113.5536464!16s%2Fg%2F11bwy_gg5k?authuser=0&entry=ttu&g_ep=EgoyMDI2MDUxMy4wIKXMDSoASAFQAw%3D%3D",
    "Pantai Watu Ulo": "https://www.google.com/maps/place/Pantai+Watu+Ulo/@-8.4252967,113.5515972,15z/data=!4m8!3m7!1s0x2dd69d2fffffffff:0x6f3d7097accf3209!8m2!3d-8.425297!4d113.561897!9m1!1b1!16s%2Fg%2F120m19h0?authuser=0&entry=ttu&g_ep=EgoyMDI2MDUxMy4wIKXMDSoASAFQAw%3D%3D",
    "Teluk Love": "https://www.google.com/maps/place/Teluk+Love/@-8.4410549,113.581323,17z/data=!3m1!4b1!4m6!3m5!1s0x2dd6942520c45715:0x3cabb1f5dd90a01b!8m2!3d-8.4410549!4d113.5838979!16s%2Fg%2F11ckkqq_1b?authuser=0&entry=ttu&g_ep=EgoyMDI2MDUxMy4wIKXMDSoASAFQAw%3D%3D",
    "Kebun Teh Gunung Gambir": "https://www.google.com/maps/place/Wisata+kebun+teh+gunung+gambir/@-8.0351906,113.4389961,17z/data=!4m7!3m6!1s0x2dd6f551f90c0c8f:0x3d1a62be1e269d12!4b1!8m2!3d-8.0351906!4d113.441571!16s%2Fg%2F11tjmzk404?authuser=0&entry=ttu&g_ep=EgoyMDI2MDUxMy4wIKXMDSoASAFQAw%3D%3D",
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
    import tempfile

    options = Options()
    # options = uc.ChromeOptions()
    options.binary_location = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
    
    options.add_argument("--lang=id")
    options.add_argument("--accept-language=id-ID,id")
    options.add_argument("--disable-blink-features=AutomationControlled")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--no-sandbox")
    options.add_argument("--no-first-run")
    options.add_argument("--no-default-browser-check")

    options.add_argument("--disable-gpu")
    options.add_argument("--disable-software-rasterizer")
    options.add_argument("--remote-debugging-port=9222")
    options.add_argument("--disable-extensions")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-gpu")

    options.add_argument(f"--user-data-dir={tempfile.mkdtemp()}")

    # User agent supaya dianggap browser asli
    options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")

    if config.get("headless"):
        options.add_argument("--headless=new")
        options.add_argument("--window-size=1920,1080")
    else:
        options.add_argument("--start-maximized")

    # if config["user_data_dir"]:
    #     options.add_argument(f"--user-data-dir={config['user_data_dir']}")

    # if config["profile"]:
    #     options.add_argument(f"--profile-directory={config['profile']}")

    return options
    driver = uc.Chrome(options=options)

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
                time.sleep(10)
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


RATING_ONLY_REVIEW_TEXT = "[Tanpa teks]"


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

        ulasan = str(first_value(item, ["text", "reviewText", "textTranslated", "snippet"], "")).strip()
        rating = normalize_rating(first_value(item, ["stars", "rating", "score"], None))
        if not ulasan and rating is None:
            continue
        if not ulasan:
            ulasan = RATING_ONLY_REVIEW_TEXT

        tanggal_text = str(first_value(item, ["publishedAtDate", "publishAt", "publishedAt", "date"], ""))
        estimated_date = estimate_review_date(tanggal_text, scrape_now)
        if config["filter_date_range"] and not is_review_in_date_range(tanggal_text, start_date, end_date, scrape_now):
            continue

        rows.append(
            {
                "wisata": wisata,
                "reviewer": first_value(item, ["name", "reviewerName", "authorName", "reviewer"], "anonymous"),
                "rating": rating,
                "ulasan": ulasan,
                "tanggal": estimated_date.strftime("%Y-%m-%d") if estimated_date else str(tanggal_text or ""),
            }
        )

    return rows


def insert_reviews(cursor, conn, reviews, periode_id):
    saved = 0
    skipped_duplicate = 0

    for review in reviews:
        execute(
            cursor,
            conn,
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

        execute(
            cursor,
            conn,
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


def count_reviews_for_wisata(cursor, conn, wisata, periode_id):
    execute(
        cursor,
        conn,
        "SELECT COUNT(*) FROM ulasan WHERE wisata = %s AND periode_id = %s",
        (wisata, periode_id),
    )
    result = cursor.fetchone()
    return int(result[0] if result else 0)


def purge_out_of_range_reviews(cursor, conn, periode_id, start_date, end_date):
    execute(cursor, conn, "SELECT id, wisata, tanggal FROM ulasan WHERE periode_id = %s", (periode_id,))
    rows = cursor.fetchall()
    deleted = 0

    for row in rows:
        review_id, wisata, tanggal = row
        if not is_review_in_date_range(tanggal, start_date, end_date):
            execute(cursor, conn, "DELETE FROM ulasan WHERE id = %s", (review_id,))
            deleted += 1
            log("INFO", f"Hapus ulasan luar rentang dari periode aktif: {wisata} ({tanggal})")

    if deleted:
        conn.commit()
    log("INFO", f"Pembersihan ulasan luar rentang: {deleted} baris dihapus.")


def validate_all_destinations_have_data(cursor, conn, periode_id, destinations, require_all=False):
    missing = []
    total_existing = 0
    for wisata in destinations:
        total = count_reviews_for_wisata(cursor, conn, wisata, periode_id)
        total_existing += total
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

    return total_existing


def validate_all_destinations_processed(processed, destinations, require_all=False):
    missing = [wisata for wisata in destinations if wisata not in processed]
    if missing and require_all:
        fail(
            "Scraper belum berhasil membuka/memproses semua lokasi: "
            + ", ".join(missing)
            + ". Cek URL atau selector Google Maps untuk lokasi tersebut."
        )

    if missing:
        log("WARNING", "Scraper belum berhasil memproses lokasi: " + ", ".join(missing))


def first_review_text(review, xpaths, attr=None):
    for xpath in xpaths:
        try:
            elements = review.find_elements("xpath", xpath)
            for element in elements:
                value = element.get_attribute(attr) if attr else element.text
                value = str(value or "").strip()
                if value:
                    return value
        except Exception:
            continue

    return ""


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
    validate_all_destinations_processed(processed, destinations, config["require_all_destinations"])
    total_existing = validate_all_destinations_have_data(cursor, conn, periode_id, destinations, config["require_all_destinations"])
    if total_existing == 0:
        fail("Scraping selesai tetapi tidak ada ulasan yang berhasil disimpan.")


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


def click_reviews_tab(driver, wait, wisata, manual_login_timeout=0):
    from selenium.webdriver.common.by import By
    from selenium.webdriver.support import expected_conditions as EC

    # candidates = [
    #     "//div[@role='tab'][contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'ulasan')]",
    #     "//div[@role='tab'][contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'review')]",
    #     "//button[@role='tab'][contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'ulasan')]",
    #     "//button[@role='tab'][contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'review')]",
    #     "//button[contains(translate(@aria-label, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'ulasan') and not(contains(translate(@aria-label, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'tulis')) and not(contains(translate(@aria-label, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'write'))]",
    #     "//button[contains(translate(@aria-label, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'review') and not(contains(translate(@aria-label, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'write'))]",
    # ]

    possible_xpaths = [
        "//button[contains(@aria-label, 'Ulasan untuk')]",
        "//button[contains(., 'Ulasan')]",
        "//div[@role='tab'][contains(., 'Ulasan')]",
        "//button[contains(@aria-label, 'Reviews')]"
    ]
    
    try:
        # wait.until(EC.presence_of_all_elements_located((By.XPATH, "//button | //div[@role='tab']")))
        all_btns = driver.find_elements(By.XPATH, "//button | //div[@role='tab']")
        time.sleep(3)
    except Exception:
        pass

    def try_click_reviews_tab():
        for xpath in possible_xpaths:
            try:
                elements = driver.find_elements(By.XPATH, xpath)
                for element in elements:
                    if element.is_displayed():
                        label = element.get_attribute("aria-label") or element.text or xpath
                        normalized_label = label.strip().lower()
                        if "tulis ulasan" in normalized_label or "write a review" in normalized_label:
                            continue
                        driver.execute_script("arguments[0].click();", element)
                        time.sleep(4)
                        log("INFO", f"{wisata}: tab ulasan dibuka lewat {label}")
                        return True
            except Exception:
                continue
        return False

    if try_click_reviews_tab():
        return True

    page_text = driver.find_element(By.TAG_NAME, "body").text.lower()
    if "tampilan terbatas" in page_text or "limited view" in page_text:
        if manual_login_timeout <= 0:
            fail(
                "Google Maps menampilkan tampilan terbatas sehingga tab Ulasan tidak tersedia. "
                "Jalankan Selenium dalam mode visible lalu login Google di jendela Chrome yang terbuka."
            )

        log(
            "WARNING",
            "Google Maps menampilkan tampilan terbatas. Silakan login Google di jendela Chrome yang terbuka. "
            f"Menunggu maksimal {manual_login_timeout} detik.",
        )
        deadline = time.time() + manual_login_timeout
        while time.time() < deadline:
            time.sleep(3)
            if try_click_reviews_tab():
                return True

        fail("Login Google belum selesai atau tab Ulasan masih tidak tersedia setelah menunggu.")

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

    # xpaths = [
    # "//div[contains(@class, 'm6U624-v79jQ-le0U9b')]", # Class kontainer ulasan umum
    # "//div[@role='main' and contains(@aria-label, 'Ulasan')]",
    # "//div[contains(@aria-label, 'Ulasan untuk')]"
    # ]

    for xpath in xpaths:
        try:
            return wait.until(EC.presence_of_element_located((By.XPATH, xpath)))
        except Exception:
            continue

    return None


def scrape_with_selenium(cursor, conn, periode_id, start_date, end_date, config, destinations):
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
    
    from selenium.webdriver.chrome.service import Service
    from webdriver_manager.chrome import ChromeDriverManager

    # driver = webdriver.Chrome(
    #     service = Service(r"C:\Users\Mufrida Farah\Downloads\chromedriver-win64\chromedriver-win64\chromedriver.exe")
    #     driver = webdriver.Chrome(service=service, options=build_chrome_options(config))
    #     options=build_chrome_options(config)
    # )


    service = Service(r"C:\Users\Mufrida Farah\Downloads\chromedriver-win64\chromedriver-win64\chromedriver.exe")
    opts = build_chrome_options(config)
    driver = webdriver.Chrome(service=service, options=opts)

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

            main_window = driver.window_handles[0]
            for handle in driver.window_handles[1:]:
                driver.switch_to.window(handle)
                driver.close()
            driver.switch_to.window(main_window)

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

            if not click_reviews_tab(driver, wait, wisata, 0 if config["headless"] else config["manual_login_timeout"]):
                log("WARNING", f"Tombol/tab ulasan tidak ditemukan untuk {wisata}")
                continue

                time.sleep(2)
                all_windows = driver.window_handles
                if len(all_windows) > 1:
                    driver.switch_to.window(all_windows[-1])
                    log("INFO", f"{wisata}: pindah ke tab baru")

                click_sort_newest(driver)

            click_sort_newest(driver)

            scrollable_div = find_reviews_scroll_container(driver, wait)
            if scrollable_div is None:
                log("WARNING", f"Container ulasan tidak ditemukan untuk {wisata}")
                continue

            last_height = 0

            driver.execute_script("arguments[0].focus();", scrollable_div)
            for i in range(config["scroll_limit"]):
                try:
                    driver.execute_script("arguments[0].scrollTop = arguments[0].scrollHeight", scrollable_div)

                    time.sleep(2)

                    new_height = driver.execute_script("return arguments[0].scrollHeight", scrollable_div)
                    log("INFO", f"{wisata}: scroll ke-{i + 1}")

                    if new_height == last_height:
                         break
                    last_height = new_height

                except Exception as e:
                    log("ERROR", f"{wisata}: Error occurred while scrolling: {e}")
                   
                scrollable_div = find_reviews_scroll_container(driver, wait)

                if scrollable_div is None:
                    break

                continue

            for button in driver.find_elements(By.XPATH, "//button[contains(text(),'Selengkapnya')]"):
                try:
                    driver.execute_script("arguments[0].click();", button)
                except Exception:
                    pass

            time.sleep(2)
            items = []
            reviews = driver.find_elements(By.XPATH, "//div[contains(@class,'jftiEf') and @data-review-id]")
            log("INFO", f"{wisata}: {len(reviews)} review ditemukan")

            for review in reviews:
                rating_label = first_review_text(
                    review,
                    [
                        ".//*[@role='img' and contains(@aria-label,'bintang')]",
                        ".//*[@role='img' and contains(@aria-label,'star')]",
                    ],
                    "aria-label",
                )
                rating = normalize_rating(rating_label)
                ulasan = first_review_text(
                    review,
                    [
                        ".//span[contains(@class,'wiI7pd')]",
                        ".//span[contains(@class,'MyEned')]",
                        ".//div[contains(@class,'MyEned')]//span",
                        ".//span[@lang]",
                    ],
                )
                if not ulasan and rating is None:
                    continue
                if not ulasan:
                    ulasan = RATING_ONLY_REVIEW_TEXT

                tanggal_text = first_review_text(
                    review,
                    [
                        ".//span[contains(@class,'rsqaWe')]",
                        ".//span[contains(@class,'xRkPPb')]",
                    ],
                )
                estimated_date = estimate_review_date(tanggal_text, scrape_now)
                if config["filter_date_range"] and not is_review_in_date_range(tanggal_text, start_date, end_date, scrape_now):
                    log("INFO", f"{wisata}: skip ulasan luar rentang ({tanggal_text})")
                    continue

                reviewer = first_review_text(
                    review,
                    [
                        ".//div[contains(@class,'d4r55')]",
                        ".//button[contains(@class,'WEBjve')]",
                    ],
                ) or "anonymous"

                items.append(
                    {
                        "wisata": wisata,
                        "reviewer": reviewer,
                        "rating": rating,
                        "ulasan": ulasan,
                        "tanggal": estimated_date.strftime("%Y-%m-%d") if estimated_date else str(tanggal_text or ""),
                    }
                )

            saved, skipped_duplicate = insert_reviews(cursor, conn, items, periode_id)
            processed.add(wisata)
            total_saved += saved
            total_skipped_duplicate += skipped_duplicate
            log("OK", f"{wisata}: simpan {saved}, duplikat dilewati {skipped_duplicate}")

        log("OK", f"Scraping Selenium selesai. Total simpan {total_saved}, duplikat dilewati {total_skipped_duplicate}.")
        validate_all_destinations_processed(processed, destinations, config["require_all_destinations"])
        total_existing = validate_all_destinations_have_data(cursor, conn, periode_id, destinations, config["require_all_destinations"])
        if total_existing == 0:
            fail("Scraping selesai tetapi tidak ada ulasan yang berhasil disimpan. Cek apakah tab ulasan Google Maps terbuka dan filter tanggal tidak terlalu ketat.")
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
    conn = make_connection(config)
    cursor = conn.cursor()

    try:
        periode_id, periode_nama = get_or_create_period(cursor, conn, start_date, end_date)
        log("INFO", f"Scraping untuk periode {periode_nama} (periode_id={periode_id})")
        log("INFO", f"Provider scraping: {scraper['provider']}")
        log("INFO", "Lokasi scraping: " + ", ".join(destinations.keys()))
        if scraper["filter_date_range"]:
            log("INFO", f"Filter aktif: ulasan {start_date.strftime('%Y-%m-%d')} sampai {end_date.strftime('%Y-%m-%d')}")
            # purge_out_of_range_reviews(cursor, conn, periode_id, start_date, end_date)

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
