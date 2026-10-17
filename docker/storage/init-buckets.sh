#!/bin/sh
# Idempoten: aman dijalankan berulang kali.
set -eu

aws configure set default.s3.addressing_style path
S3="aws --endpoint-url ${S3_ENDPOINT}"

i=0
until $S3 s3api list-buckets >/dev/null 2>&1; do
  i=$((i + 1))
  if [ "$i" -ge 30 ]; then
    echo "Object storage tidak bisa dijangkau di ${S3_ENDPOINT}" >&2
    exit 1
  fi
  echo "Menunggu object storage..."
  sleep 2
done

for bucket in "$PUBLIC_BUCKET" "$PRIVATE_BUCKET"; do
  if $S3 s3api head-bucket --bucket "$bucket" >/dev/null 2>&1; then
    echo "Bucket $bucket sudah ada"
  else
    $S3 s3api create-bucket --bucket "$bucket"
    echo "Bucket $bucket dibuat"
  fi
done

# Bucket publik: siapa pun boleh membaca objek, tapi tidak boleh me-list isi bucket.
$S3 s3api put-bucket-policy --bucket "$PUBLIC_BUCKET" --policy "{
  \"Version\": \"2012-10-17\",
  \"Statement\": [{
    \"Sid\": \"PublicReadObjects\",
    \"Effect\": \"Allow\",
    \"Principal\": {\"AWS\": [\"*\"]},
    \"Action\": [\"s3:GetObject\"],
    \"Resource\": [\"arn:aws:s3:::${PUBLIC_BUCKET}/*\"]
  }]
}"
echo "Policy baca-publik diterapkan ke $PUBLIC_BUCKET; $PRIVATE_BUCKET tetap privat"
