#!/usr/bin/env bash
#
# Sunucudaki canlı klasörü GitHub'daki son sürümle eşitler.
#
# Neden: Canlı klasör aynı zamanda git deposudur. Dosyalar elle yüklendiğinde
# çalışma dizini commit'ten farklılaşır ve cPanel "Update from Remote" işlemi
# başarısız olur. Bu script yerel değişiklikleri silmeden yedekler, ardından
# uzak depodaki dalı çeker.
#
# Girdi (isteğe bağlı ortam değişkenleri):
#   SYNC_REMOTE  Uzak depo adı   (varsayılan: origin)
#   SYNC_BRANCH  Eşitlenecek dal (varsayılan: main)
#
# Çıktı: Başarıda 0, hatada sıfırdan farklı kod ve açıklayıcı mesaj.
#
# Güvenlik: Yalnızca git'in izlediği dosyalar etkilenir. .env, yüklenen
# dosyalar ve loglar gibi izlenmeyen dosyalara dokunulmaz. Silinen yerel
# değişiklikler "git stash list" ile geri alınabilir.
#
# Kullanım (sunucuda): bash scripts/sync-server.sh

set -euo pipefail

readonly REMOTE="${SYNC_REMOTE:-origin}"
readonly BRANCH="${SYNC_BRANCH:-main}"
readonly STASH_IDENTITY_NAME="server-sync"
readonly STASH_IDENTITY_EMAIL="server-sync@localhost"

log() {
    printf '[sync-server] %s\n' "$1"
}

fail() {
    printf '[sync-server] HATA: %s\n' "$1" >&2
    exit 1
}

# Script'in bulunduğu dizinin bir üstü, depo kökü olmalıdır.
resolve_repository_root() {
    local script_dir
    script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    cd "${script_dir}/.."
    git rev-parse --is-inside-work-tree >/dev/null 2>&1 || fail "Bu dizin bir git deposu değil."
}

has_tracked_changes() {
    [[ -n "$(git status --porcelain --untracked-files=no)" ]]
}

# Yerel değişiklikleri silmeden yedekler; stash içeriği daha sonra incelenebilir.
backup_local_changes() {
    local label
    label="sunucu-yedek-$(date +%Y%m%d-%H%M%S)"

    log "Commit'lenmemiş değişiklikler bulundu, yedekleniyor: ${label}"
    git status --short --untracked-files=no
    git -c user.name="${STASH_IDENTITY_NAME}" -c user.email="${STASH_IDENTITY_EMAIL}" \
        stash push --message "${label}" >/dev/null
}

fetch_and_fast_forward() {
    log "Uzak depo çekiliyor: ${REMOTE}/${BRANCH}"
    git fetch "${REMOTE}" "${BRANCH}" || fail "Uzak depoya ulaşılamadı (${REMOTE})."

    git checkout "${BRANCH}" >/dev/null 2>&1 || fail "'${BRANCH}' dalına geçilemedi."
    git merge --ff-only FETCH_HEAD || fail "Fast-forward yapılamadı; yerel dal uzaktan sapmış."
}

main() {
    resolve_repository_root

    if has_tracked_changes; then
        backup_local_changes
    fi

    fetch_and_fast_forward

    log "Tamamlandı. Güncel sürüm: $(git log -1 --format='%h %s')"
}

main "$@"
