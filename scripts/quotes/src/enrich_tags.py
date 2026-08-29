#!/usr/bin/env python3
"""Tokenize quotes and attach semantic / emotion / usage tags + scores."""
from __future__ import annotations

import json
import re
import sys
from collections import Counter
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from dbutil import connect, init_schema  # noqa: E402

STOP = {
    "a", "an", "the", "and", "or", "but", "if", "to", "of", "in", "on", "for", "with",
    "is", "are", "was", "were", "be", "been", "am", "i", "you", "he", "she", "we", "they",
    "it", "my", "your", "his", "her", "our", "their", "me", "him", "them", "this", "that",
    "not", "no", "yes", "so", "as", "at", "by", "from", "into", "just", "do", "did", "does",
    "have", "has", "had", "will", "would", "can", "could", "should", "about", "what", "when",
    "who", "how", "why", "all", "im", "ive", "dont", "youre", "its", "thats",
}

# label -> (kind, keywords)
TAG_RULES: list[tuple[str, str, str, list[str]]] = [
    ("trope:confession", "trope", "Confession", ["love you", "i love", "confess", "marry me", "like you"]),
    ("trope:rain_umbrella", "trope", "Rain / umbrella", ["rain", "umbrella", "wet", "storm"]),
    ("trope:destiny_fate", "trope", "Destiny / fate", ["fate", "destiny", "meant to", "red thread", "reincarn"]),
    ("trope:workplace", "trope", "Workplace", ["secretary", "office", "boss", "contract", "company", "overtime"]),
    ("trope:status_gap", "trope", "Status gap", ["rich", "poor", "chaebol", "heir", "commoner", "ceo"]),
    ("trope:second_lead", "trope", "Second lead ache", ["friend", "wait", "still love", "let go"]),
    ("trope:found_family", "trope", "Found family", ["together", "family", "friend", "side"]),
    ("trope:time_memory", "trope", "Time / memory", ["remember", "forget", "memory", "years", "waited"]),
    ("emotion:longing", "emotion", "Longing", ["miss", "wait", "lonely", "alone", "away", "distance"]),
    ("emotion:tenderness", "emotion", "Tenderness", ["soft", "gentle", "warm", "hold", "care", "protect"]),
    ("emotion:humor", "emotion", "Humor", ["funny", "joke", "idiot", "stupid", "crazy", "weird"]),
    ("emotion:resolve", "emotion", "Resolve", ["promise", "never", "always", "won't", "will", "fight"]),
    ("emotion:melancholy", "emotion", "Melancholy", ["sorry", "hurt", "cry", "tear", "pain", "sad"]),
    ("desk:soft_craft", "desk", "Soft craft example", ["love", "heart", "feel", "remember", "together", "home"]),
    ("desk:mirror_roast_safe", "desk", "Mirror-safe (device, not person)", ["contract", "fate", "destiny", "secretary", "umbrella"]),
    ("usage:epigraph_ok", "usage", "Epigraph candidate", ["love", "heart", "fate", "home", "wait", "remember"]),
    ("usage:inline_example_ok", "usage", "Inline example candidate", ["love", "wait", "promise", "together"]),
    ("usage:merch_forbidden", "usage", "Do not use on merch", ["copyright", "™"]),  # always also force-applied
]

POS = {"love", "heart", "home", "warm", "together", "promise", "always", "smile", "happy", "hope", "beautiful"}
NEG = {"sorry", "hurt", "cry", "hate", "pain", "alone", "lonely", "die", "leave", "afraid", "fear", "tear"}
AROUSAL = {"love", "hate", "cry", "fight", "scream", "kiss", "run", "never", "always", "fate", "destiny"}


def ensure_tag(conn, slug: str, kind: str, label: str) -> int:
    conn.execute(
        "INSERT INTO tags (slug, kind, label) VALUES (?, ?, ?) ON CONFLICT(slug) DO UPDATE SET label=excluded.label",
        (slug, kind, label),
    )
    row = conn.execute("SELECT id FROM tags WHERE slug=?", (slug,)).fetchone()
    return int(row[0])


def tokenize(text: str) -> list[str]:
    words = re.findall(r"[a-z0-9']+", text.lower())
    return [w for w in words if w not in STOP and len(w) > 2]


def emotion_scores(text: str, tokens: list[str]) -> tuple[float, float, list[str]]:
    low = text.lower()
    pos = sum(1 for t in tokens if t in POS)
    neg = sum(1 for t in tokens if t in NEG)
    total = max(1, pos + neg)
    valence = (pos - neg) / total
    arousal = min(1.0, 0.25 + 0.15 * sum(1 for t in tokens if t in AROUSAL))
    labels = []
    for slug, kind, _label, keys in TAG_RULES:
        if kind != "emotion":
            continue
        if any(_key_hit(low, k) for k in keys):
            labels.append(slug.split(":", 1)[1])
    if not labels:
        if valence >= 0.25:
            labels.append("tenderness")
        elif valence <= -0.25:
            labels.append("melancholy")
        else:
            labels.append("longing")
    return max(-1.0, min(1.0, valence)), arousal, labels


def _key_hit(low: str, key: str) -> bool:
    """Word-aware match so 'rain' does not hit inside 'train'."""
    key = key.lower().strip()
    if " " in key:
        return key in low
    return re.search(rf"(?<![a-z0-9]){re.escape(key)}(?![a-z0-9])", low) is not None


def match_tags(text: str) -> list[tuple[str, str, str]]:
    low = text.lower()
    hits = []
    for slug, kind, label, keys in TAG_RULES:
        if slug == "usage:merch_forbidden":
            continue
        if any(_key_hit(low, k) for k in keys):
            hits.append((slug, kind, label))
    # Always forbid merch use of third-party dialogue in this corpus
    hits.append(("usage:merch_forbidden", "usage", "Do not use on merch"))
    # Country tags applied from work join later
    return hits


def popularity_for(conn, quote_id: int, text_norm: str, section_hint: str | None) -> float:
    dupes = conn.execute(
        "SELECT COUNT(*) AS c FROM quotes WHERE quote_text_norm=?",
        (text_norm,),
    ).fetchone()["c"]
    score = 40.0
    score += min(30.0, 8.0 * max(0, dupes - 1))
    sec = (section_hint or "").lower()
    if "dialogue" in sec or "quotes" in sec or "cast" in sec:
        score += 10
    if "theme" in sec or "about" in sec:
        score += 5
    # shorter famous lines often travel more
    wc = conn.execute("SELECT word_count FROM quotes WHERE id=?", (quote_id,)).fetchone()["word_count"]
    if wc <= 12:
        score += 8
    elif wc <= 18:
        score += 4
    return max(0.0, min(100.0, score))


def main() -> int:
    conn = connect()
    init_schema(conn)

    # country tags
    for slug, label in [("country:K", "Korea"), ("country:C", "China"), ("country:J", "Japan")]:
        ensure_tag(conn, slug, "country", label)

    rows = conn.execute(
        """
        SELECT q.id, q.quote_text, q.quote_text_norm, q.section_hint, w.country
        FROM quotes q
        JOIN works w ON w.id = q.work_id
        """
    ).fetchall()

    for row in rows:
        qid = int(row["id"])
        text = row["quote_text"]
        tokens = tokenize(text)
        valence, arousal, elabels = emotion_scores(text, tokens)
        pop = popularity_for(conn, qid, row["quote_text_norm"], row["section_hint"])

        conn.execute(
            """
            UPDATE quotes SET
                semantic_tokens=?,
                emotion_valence=?,
                emotion_arousal=?,
                emotion_labels=?,
                popularity_score=?,
                updated_at=datetime('now')
            WHERE id=?
            """,
            (
                json.dumps(tokens),
                valence,
                arousal,
                json.dumps(elabels),
                pop,
                qid,
            ),
        )

        conn.execute("DELETE FROM quote_tags WHERE quote_id=?", (qid,))
        tag_hits = match_tags(text)
        tag_hits.append((f"country:{row['country']}", "country", row["country"]))
        for slug, kind, label in tag_hits:
            tid = ensure_tag(conn, slug, kind, label)
            conn.execute(
                "INSERT OR IGNORE INTO quote_tags (quote_id, tag_id) VALUES (?, ?)",
                (qid, tid),
            )

    conn.commit()

    # light popularity bump: token rarity inverse within corpus
    all_tokens = Counter()
    for r in conn.execute("SELECT semantic_tokens FROM quotes"):
        all_tokens.update(json.loads(r["semantic_tokens"] or "[]"))

    conn.close()
    print(f"enriched {len(rows)} quotes")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
