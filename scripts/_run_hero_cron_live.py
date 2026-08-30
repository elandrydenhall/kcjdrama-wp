#!/usr/bin/env python3
import os
from pathlib import Path

import paramiko

ROOT = Path(r"C:\Scripts\wp-dev\sites\kcjdrama")
WP = "/home/u628528567/domains/kcjdrama.com/public_html"


def load_dotenv(path: Path) -> None:
    if not path.is_file():
        return
    for raw in path.read_text(encoding="utf-8-sig").splitlines():
        line = raw.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, val = line.partition("=")
        os.environ.setdefault(key.strip(), val.strip().strip('"').strip("'"))


load_dotenv(ROOT / ".env")
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(
    os.environ["KCJ_SSH_HOST"],
    port=int(os.environ.get("KCJ_SSH_PORT", "65002")),
    username=os.environ["KCJ_SSH_USER"],
    password=os.environ["KCJ_SSH_PASSWORD"],
    timeout=45,
    allow_agent=False,
    look_for_keys=False,
)
local = ROOT / "scripts" / "_hero_cron_diagnose.php"
remote = "/tmp/_hero_cron_diagnose.php"
sftp = client.open_sftp()
sftp.put(str(local), remote)
sftp.close()
cmd = (
    f"php '{remote}' '{WP}'; "
    f"echo '==== RUN ===='; "
    f"php '{remote}' '{WP}' --run; "
    f"rm -f '{remote}'"
)
_, stdout, stderr = client.exec_command(cmd, timeout=120)
print(stdout.read().decode("utf-8", "replace"))
err = stderr.read().decode("utf-8", "replace")
if err.strip():
    print("ERR", err[:800])
client.close()
