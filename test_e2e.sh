#!/bin/bash
set -e

echo "1. Login..."
curl -c cookies.txt -s http://127.0.0.1:8080/api/auth/csrf | grep -o '"csrfToken":"[^"]*"' | cut -d'"' -f4 > csrf.txt
CSRF=$(cat csrf.txt)
curl -s -c cookies.txt -b cookies.txt -X POST http://127.0.0.1:8080/api/auth/callback/credentials \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "username=admin_test&password=password123&csrfToken=$CSRF" > /dev/null

echo "2. Tambah Pelanggan..."
RES_CUST=$(curl -s -b cookies.txt -X POST "http://127.0.0.1:8080/api?action=tambah_pelanggan" \
  -F "nama=Customer E2E" \
  -F "alamat=Jalan Workflow" \
  -F "no_whatsapp=08122334455")
echo "Response: $RES_CUST"
CUST_ID=$(echo $RES_CUST | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

echo "3. Buat Pesanan Baru..."
RES_ORDER=$(curl -s -b cookies.txt -X POST "http://127.0.0.1:8080/api?action=buat_pesanan" \
  -F "pelanggan_id=$CUST_ID" \
  -F "deskripsi_pesanan=Cetak Banner E2E 3x2" \
  -F "estimasi_harga=150000" \
  -F "ongkos_kirim=15000")
echo "Response: $RES_ORDER"
ORDER_ID=$(echo $RES_ORDER | grep -o '"orderId":[0-9]*' | head -1 | cut -d':' -f2)
TOKEN=$(echo $RES_ORDER | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

echo "4. Update Pilihan Pembayaran (DP 50000)..."
RES_PAY=$(curl -s -b cookies.txt -X POST "http://127.0.0.1:8080/api?action=update_pilihan_pembayaran" \
  -F "id=$ORDER_ID" \
  -F "token=$TOKEN" \
  -F "metode_pembayaran=Transfer" \
  -F "is_dp=true" \
  -F "jumlah_dp=50000")
echo "Response: $RES_PAY"

echo "5. Update Status (Diproses)..."
RES_STATUS=$(curl -s -b cookies.txt -X POST "http://127.0.0.1:8080/api?action=update_status" \
  -F "id=$ORDER_ID" \
  -F "status=Diproses")
echo "Response: $RES_STATUS"

echo "6. Lunasi Pembayaran..."
RES_LUNAS=$(curl -s -b cookies.txt -X POST "http://127.0.0.1:8080/api?action=lunasi_pembayaran" \
  -F "id=$ORDER_ID" \
  -F "metode_pembayaran=Transfer")
echo "Response: $RES_LUNAS"

echo "7. Update Status (Selesai)..."
RES_FINISH=$(curl -s -b cookies.txt -X POST "http://127.0.0.1:8080/api?action=update_status" \
  -F "id=$ORDER_ID" \
  -F "status=Selesai")
echo "Response: $RES_FINISH"

echo "======================================"
echo "DB Check:"
mysql -u root -e "USE db_mpem; SELECT id, pelanggan_id, deskripsi_pesanan, total_harga, jumlah_dp, status, status_pembayaran FROM pesanan WHERE id = $ORDER_ID;"
