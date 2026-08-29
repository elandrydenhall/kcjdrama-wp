# KCJ quotes corpus

Sourced, tagged short quotes from **K / C / J romance TV dramas** for later Soft commentary examples.

**Not wired into the WordPress theme yet.** Review CSV → mark verified → only then discuss page epigraphs.

## Layout

| Path | Role |
|------|------|
| `kcj_quotes.sqlite3` | Canonical DB |
| `schema.sql` | DDL |
| `seeds/seed_works.csv` | Target dramas |
| `src/` | Pipeline scripts |
| `raw/` | Scrape snapshots (gitignored) |
| `export/quotes.csv` | Human review export |

## Guardrails

- Max **25 words** per quote
- Mandatory `source_url`, `source_name`, `license_note`
- Wikiquote-first; no subtitle/script dumps
- `verified=0` until you approve rows
- `usage:merch_forbidden` applied to all third-party dialogue

## Commands

From this folder (or any cwd):

```powershell
cd C:\Scripts\wp-dev\sites\kcjdrama\scripts\quotes

# Fast path: curated sourced rows + enrich/export
python src\run_pipeline.py

# Optional URL liveness check on curated sources
python src\run_pipeline.py --check-urls

# Also hit Wikiquote (slow; many K-dramas have no page; rate-limited)
python src\run_pipeline.py --with-wikiquote
```

Pieces:
- `python src\ingest_curated.py` — `seeds/seed_quotes.csv`
- `python src\ingest_wikiquote.py [--country K] [--limit 5]`
- `python src\enrich_tags.py` / `validate.py` / `export_csv.py`

## Review / verify

```powershell
python src\mark_verified.py --all
python src\mark_verified.py --country K
python src\mark_verified.py --ids 1,2,5-9
```

Re-export after changes: `python src\export_csv.py`

Theme “large quote” placement is a **separate ask**.

## Fill more quotes

```powershell
# edit seeds\seed_quotes.csv then:
python src\ingest_curated.py
# and/or edit seeds\scrape_sources.csv then:
python src\ingest_html_sources.py
python src\enrich_tags.py
python src\mark_verified.py --all
python src\export_csv.py
```
