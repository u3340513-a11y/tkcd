#!/usr/bin/env bash
#
# PHP geliştirme sunucusunu başlatan yardımcı script.
#
# Neden: php -S sürecinin, onu başlatan kabuk (örn. Cursor'un alt kabuğu)
# kapandığında ölmemesi için nohup + & + disown ile yeni bir oturumda
# ayrıştırılır; doc-root /proje-kökü/public, router ise /proje-kökü
# içindeki router.php dosyasıdır. Mutlak yollar kullanılarak script'in
# çalıştırıldığı dizinden bağımsız çalışması sağlanır.
set -euo pipefail

# Script'in yaşadığı dizine göre proje kökünü hesapla.
PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PUBLIC_DIR="$PROJECT_ROOT/public"
ROUTER="$PROJECT_ROOT/router.php"

if [ ! -f "$ROUTER" ]; then
    echo "Router bulunamadı: $ROUTER" >&2
    exit 1
fi
if [ ! -d "$PUBLIC_DIR" ]; then
    echo "public/ dizini bulunamadı: $PUBLIC_DIR" >&2
    exit 1
fi

mkdir -p /tmp
LOG="/tmp/kamu-server.log"
PIDFILE="/tmp/kamu-server.pid"

# Halen çalışan bir sunucu varsa durduralım.
if [ -f "$PIDFILE" ]; then
    OLD_PID="$(cat "$PIDFILE" 2>/dev/null || true)"
    if [ -n "$OLD_PID" ] && kill -0 "$OLD_PID" 2>/dev/null; then
        kill "$OLD_PID" 2>/dev/null || true
        sleep 1
    fi
    rm -f "$PIDFILE"
fi

# Port 8787'de dinleyen php süreçlerini de süpürelim.
lsof -ti :8787 2>/dev/null | xargs -r kill 2>/dev/null || true
sleep 1

# nohup + & + disown, bağlı olduğu kabuk ölse bile sürecin yaşamasını sağlar.
nohup php -S 127.0.0.1:8787 -t "$PUBLIC_DIR" "$ROUTER" >"$LOG" 2>&1 < /dev/null &
NEW_PID=$!
disown "$NEW_PID" 2>/dev/null || true
echo "$NEW_PID" > "$PIDFILE"

# Birkaç saniye bekle ve sağlık kontrolü yap.
sleep 2
if curl -sS -o /dev/null -w "%{http_code}" http://127.0.0.1:8787/ | grep -q '^200$'; then
    echo "Sunucu ayakta: http://127.0.0.1:8787/ (PID: $NEW_PID, log: $LOG)"
else
    echo "Sunucu başlatılamadı, logu kontrol edin: $LOG" >&2
    tail -n 50 "$LOG" >&2 || true
    exit 1
fi
