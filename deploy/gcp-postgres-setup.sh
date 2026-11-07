#!/bin/bash
# Permanent DB on GCP: Cloud SQL (PostgreSQL) + Cloud Run
# Run in Cloud Shell from repo root: bash deploy/gcp-postgres-setup.sh
set -euo pipefail
cd "$(dirname "$0")/.."

PROJECT="${PROJECT:-resume-508612}"
REGION="${REGION:-asia-south1}"
INSTANCE="${INSTANCE:-seo-postgres}"
DB_NAME="${DB_NAME:-seo_system}"
DB_USER="${DB_USER:-seo_user}"
DB_PASS="${DB_PASS:-ChangeMe_Strong_2026!}"
ROOT_PASS="${ROOT_PASS:-$DB_PASS}"

echo "=== 1) Cloud SQL PostgreSQL instance (skip if already RUNNABLE) ==="
if ! gcloud sql instances describe "$INSTANCE" --project="$PROJECT" &>/dev/null; then
  gcloud sql instances create "$INSTANCE" \
    --database-version=POSTGRES_15 \
    --tier=db-f1-micro \
    --region="$REGION" \
    --root-password="$ROOT_PASS" \
    --project="$PROJECT"
else
  echo "Instance $INSTANCE already exists."
fi

echo "=== 2) Database + app user ==="
gcloud sql databases create "$DB_NAME" --instance="$INSTANCE" --project="$PROJECT" 2>/dev/null || true
gcloud sql users create "$DB_USER" --instance="$INSTANCE" --password="$DB_PASS" --project="$PROJECT" 2>/dev/null \
  || gcloud sql users set-password "$DB_USER" --instance="$INSTANCE" --password="$DB_PASS" --project="$PROJECT"

CONN="$PROJECT:$REGION:$INSTANCE"
echo "Connection: $CONN"

grep -q '^php.ini' .dockerignore 2>/dev/null || echo 'php.ini' >> .dockerignore

echo "=== 3) Deploy Cloud Run (needs updated Dockerfile + config.php with pgsql support) ==="
gcloud run deploy seo \
  --source . \
  --region="$REGION" \
  --project="$PROJECT" \
  --add-cloudsql-instances="$CONN" \
  --timeout=600 \
  --set-env-vars="DB_TYPE=pgsql,DB_HOST=/cloudsql/$CONN,DB_NAME=$DB_NAME,DB_USER=$DB_USER,DB_PASS=$DB_PASS"

echo ""
echo "Done. Open setup: https://seo-173907682716.asia-south1.run.app/setup.php"
echo "Login: admin / admin123 (first empty DB)"
