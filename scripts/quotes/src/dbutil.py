#!/usr/bin/env python3
"""Shared paths and SQLite helpers for the KCJ quotes corpus."""
from __future__ import annotations

import re
import sqlite3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DB_PATH = ROOT / "kcj_quotes.sqlite3"
SCHEMA_PATH = ROOT / "schema.sql"
SEEDS_PATH = ROOT / "seeds" / "seed_works.csv"
EXPORT_DIR = ROOT / "export"
RAW_DIR = ROOT / "raw"

WORD_CAP = 25
USER_AGENT = "kcjdrama-quotes-corpus/1.0 (+local research; respectful rate limit)"


def connect(db_path: Path | None = None) -> sqlite3.Connection:
    path = db_path or DB_PATH
    conn = sqlite3.connect(path)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON")
    return conn


def init_schema(conn: sqlite3.Connection) -> None:
    sql = SCHEMA_PATH.read_text(encoding="utf-8")
    conn.executescript(sql)
    conn.commit()


def normalize_quote(text: str) -> str:
    t = text.strip().strip("\"'“”‘’")
    t = re.sub(r"\s+", " ", t)
    return t.lower()


def word_count(text: str) -> int:
    return len(re.findall(r"[A-Za-z0-9']+", text))


def acceptable_length(text: str) -> bool:
    wc = word_count(text)
    return 3 <= wc <= WORD_CAP
