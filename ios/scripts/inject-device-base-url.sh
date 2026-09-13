#!/bin/bash
# Injects the Mac LAN IP into Debug Info.plist so a physical iPhone can reach Laravel.
# Simulator keeps BASE_URL=127.0.0.1. Do not commit a LAN IP into Swift.
set -euo pipefail

if [ "${CONFIGURATION:-}" != "Debug" ]; then
  exit 0
fi

IP="$(ipconfig getifaddr en0 2>/dev/null || true)"
if [ -z "${IP}" ]; then
  IP="$(ipconfig getifaddr en1 2>/dev/null || true)"
fi

if [ -z "${IP}" ]; then
  echo "note: no LAN IP found; physical-device Debug will keep loopback BASE_URL"
  exit 0
fi

URL="http://${IP}:8000"
PLIST="${TARGET_BUILD_DIR:-}/${INFOPLIST_PATH:-}"

if [ ! -f "${PLIST}" ]; then
  echo "note: Info.plist not found at ${PLIST}; skip DEVICE_BASE_URL inject"
  exit 0
fi

/usr/libexec/PlistBuddy -c "Delete :DEVICE_BASE_URL" "${PLIST}" 2>/dev/null || true
/usr/libexec/PlistBuddy -c "Add :DEVICE_BASE_URL string ${URL}" "${PLIST}"
echo "Injected DEVICE_BASE_URL=${URL}"
