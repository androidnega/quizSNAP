#!/usr/bin/env bash
# Run from your Mac (will prompt for SSH key passphrase if needed):
#   bash scripts/local-deploy-to-production.sh
set -euo pipefail

HOST="${QUIZSNAP_SSH_HOST:-root@80.241.223.82}"
SSH_KEY="${QUIZSNAP_SSH_KEY:-$HOME/.ssh/quizit_id_rsa}"

echo "==> Pushing latest main to GitHub..."
git push origin main

echo "==> Deploying on ${HOST}..."
ssh -i "$SSH_KEY" "$HOST" 'cd /srv/apps/quizsnap && bash deploy.sh && bash scripts/vps/check-reverb-websocket.sh | tail -20'

echo ""
echo "Verify birthday banner asset:"
curl -s -o /dev/null -w "student-dashboard-birthday-banner.webp → HTTP %{http_code}\n" \
  "https://quizsnap.online/images/student-dashboard-birthday-banner.webp"
