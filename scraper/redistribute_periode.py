# -*- coding: utf-8 -*-
"""
redistribute_periode.py
-----------------------
Memisahkan ulasan 2025 yang salah masuk ke periode Mei 2026,
lalu memindahkan ke periode yang benar berdasarkan kolom `tanggal`.

Jalankan dari root project Laravel:
    python scripts/redistribute_periode.py

Atau dengan --dry-run untuk preview tanpa mengubah data:
    python scripts/redistribute_periode.py --dry-run
"""

import sys
import argparse
from pathlib import Path
from datetime import datetime

# ── path setup ──────────────────────────────────────────────────────────────
BASE_DIR = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(Path(__file__).resolve().parent))

# re-use helper dari scraping_pipeline
from scraping_pipeline import (
    db_config,
    make_connection,
    prepare_sql,
    execute,
    is_sqlite_connection,
)

NAMA_BULAN = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
]


def log(level, msg):
    print(f"[{level}] {msg}", flush=True)


def parse_args():
    p = argparse.ArgumentParser()
    p.add_argument(
        "--dry-run",
        action="store_true",
        help="Preview perubahan tanpa benar-benar mengubah database.",
    )
    p.add_argument(
        "--tahun",
        type=int,
        default=2025,
        help="Tahun ulasan yang akan dipisah. Default: 2025.",
    )
    return p.parse_args()


def get_or_create_periode(cursor, conn, bulan, tahun, dry_run):
    """Ambil id periode yang ada, atau buat baru jika belum ada."""
    execute(
        cursor, conn,
        "SELECT id FROM periode_analisis WHERE bulan = %s AND tahun = %s LIMIT 1",
        (bulan, tahun),
    )
    row = cursor.fetchone()
    if row:
        return row[0]

    nama = f"{NAMA_BULAN[bulan - 1]} {tahun}"
    if dry_run:
        log("DRY-RUN", f"Akan buat periode baru: {nama}")
        return None  # belum ada id nyata

    execute(
        cursor, conn,
        """
        INSERT INTO periode_analisis (nama, bulan, tahun, created_at, updated_at)
        VALUES (%s, %s, %s, NOW(), NOW())
        """,
        (nama, bulan, tahun),
    )
    conn.commit()
    new_id = cursor.lastrowid
    log("OK", f"Periode baru dibuat: {nama} (id={new_id})")
    return new_id


def main():
    args = parse_args()
    dry_run = args.dry_run
    tahun = args.tahun

    if dry_run:
        log("INFO", "Mode DRY-RUN — tidak ada perubahan yang disimpan.")

    config = db_config()
    conn   = make_connection(config)
    cursor = conn.cursor()

    # ── 1. Ambil semua ulasan tahun target yang periode-nya salah ───────────
    # "Salah" = bulan/tahun di kolom tanggal tidak cocok dengan periode_analisis
    if is_sqlite_connection(conn):
        sql_fetch = """
            SELECT u.id, u.tanggal, u.wisata, u.periode_id,
                   p.bulan AS p_bulan, p.tahun AS p_tahun
            FROM ulasan u
            JOIN periode_analisis p ON u.periode_id = p.id
            WHERE strftime('%Y', u.tanggal) = ?
              AND (
                  CAST(strftime('%m', u.tanggal) AS INTEGER) != p.bulan
                  OR CAST(strftime('%Y', u.tanggal) AS INTEGER) != p.tahun
              )
        """
        cursor.execute(sql_fetch, (str(tahun),))
    else:
        sql_fetch = """
            SELECT u.id, u.tanggal, u.wisata, u.periode_id,
                   p.bulan AS p_bulan, p.tahun AS p_tahun
            FROM ulasan u
            JOIN periode_analisis p ON u.periode_id = p.id
            WHERE YEAR(u.tanggal) = %s
              AND (
                  MONTH(u.tanggal) != p.bulan
                  OR YEAR(u.tanggal)  != p.tahun
              )
        """
        cursor.execute(sql_fetch, (tahun,))

    rows = cursor.fetchall()
    log("INFO", f"Ditemukan {len(rows)} ulasan {tahun} dengan periode tidak sesuai tanggal.")

    if not rows:
        log("OK", "Tidak ada data yang perlu dipindahkan.")
        conn.close()
        return

    # ── 2. Kelompokkan per bulan ─────────────────────────────────────────────
    from collections import defaultdict
    grouped = defaultdict(list)   # (bulan, tahun) → [id, ...]

    for row in rows:
        ulasan_id, tanggal, wisata, periode_id, p_bulan, p_tahun = row
        try:
            if isinstance(tanggal, str):
                dt = datetime.strptime(tanggal[:10], "%Y-%m-%d")
            else:
                dt = tanggal
            grouped[(dt.month, dt.year)].append(ulasan_id)
        except Exception as e:
            log("WARNING", f"Skip id={ulasan_id} tanggal='{tanggal}': {e}")

    # ── 3. Preview ───────────────────────────────────────────────────────────
    log("INFO", "Rencana pemindahan:")
    for (bulan, thn), ids in sorted(grouped.items()):
        nama = f"{NAMA_BULAN[bulan - 1]} {thn}"
        log("INFO", f"  → {nama}: {len(ids)} ulasan")

    if dry_run:
        log("DRY-RUN", "Selesai preview. Jalankan tanpa --dry-run untuk terapkan perubahan.")
        conn.close()
        return

    # ── 4. Terapkan pemindahan ───────────────────────────────────────────────
    total_moved = 0
    for (bulan, thn), ids in sorted(grouped.items()):
        periode_id = get_or_create_periode(cursor, conn, bulan, thn, dry_run=False)
        if periode_id is None:
            continue

        # Update per batch
        placeholders = ",".join(["%s"] * len(ids))
        if is_sqlite_connection(conn):
            placeholders = ",".join(["?"] * len(ids))

        cursor.execute(
            f"UPDATE ulasan SET periode_id = %s, updated_at = NOW() WHERE id IN ({placeholders})".replace(
                "%s", "?" if is_sqlite_connection(conn) else "%s"
            ),
            [periode_id] + ids,
        )
        conn.commit()

        nama = f"{NAMA_BULAN[bulan - 1]} {thn}"
        log("OK", f"{nama}: {len(ids)} ulasan dipindahkan ke periode_id={periode_id}")
        total_moved += len(ids)

    log("OK", f"Selesai. Total {total_moved} ulasan berhasil dipindahkan ke periode yang benar.")
    conn.close()


if __name__ == "__main__":
    main()