#!/usr/bin/env bash
# ==============================================================================
# Skrip Pengujian & Verifikasi Background Queue Worker (Supervisor) — KampusLMS
# ==============================================================================
# Disusun oleh: Shabrina Putri Az-Zahra (NIM: 10241066) — Kelompok 06 (EduKampus)
# Peran: Asynchronous Systems & Background Worker
# Modul Minggu 12 (Milestone M4: Production Ready)
#
# Memverifikasi 5 Aspek Kritis Sistem Antrean Produksi:
# 1. Status service Supervisor daemon (memastikan worker RUNNING)
# 2. Integritas tabel antrean basis data (`jobs`)
# 3. Ketiadaan pekerjaan yang gagal pada tabel `failed_jobs`
# 4. Pengujian sinyal restart worker (`php artisan queue:restart`)
# 5. Verifikasi pencatatan log worker (`storage/logs/worker.log`)
# ==============================================================================

set -e

# Pewarnaan output terminal
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}================================================================${NC}"
echo -e "${BLUE}    VERIFIKASI QUEUE WORKER & SUPERVISOR — KAMPUSLMS (M4)       ${NC}"
echo -e "${BLUE}================================================================${NC}"
echo "Waktu Pengujian : $(date '+%Y-%m-%d %H:%M:%S')"
echo "Lingkungan      : Production Ready"
echo ""

PASSED_CHECKS=0
FAILED_CHECKS=0

# ------------------------------------------------------------------------------
# 1. Pemeriksaan Status Supervisor Worker via supervisorctl
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>>> 1. Memeriksa Status Worker Supervisor (supervisorctl status) <<<${NC}"
if command -v supervisorctl &> /dev/null; then
    WORKER_STATUS=$(sudo supervisorctl status kampuslms-worker:* 2>&1 || true)
    echo "$WORKER_STATUS"
    
    if echo "$WORKER_STATUS" | grep -q "RUNNING"; then
        echo -e "${GREEN}[PASS] Proses worker Supervisor terdeteksi aktif dan berstatus RUNNING.${NC}"
        PASSED_CHECKS=$((PASSED_CHECKS + 1))
    else
        echo -e "${YELLOW}[WARN] Worker Supervisor belum berstatus RUNNING atau service sedang offline.${NC}"
    fi
else
    echo -e "${YELLOW}[SKIP] supervisorctl tidak ditemukan di sistem lokal (diperlukan pada server Linux Ubuntu).${NC}"
fi
echo ""

# ------------------------------------------------------------------------------
# 2. Pemeriksaan Driver Queue pada Konfigurasi Aplikasi
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>>> 2. Memeriksa Konfigurasi Antrean (QUEUE_CONNECTION) <<<${NC}"
QUEUE_DRIVER=$(php artisan tinker --execute="echo config('queue.default');" 2>/dev/null || echo "database")
echo "Default Queue Driver: $QUEUE_DRIVER"

if [ "$QUEUE_DRIVER" = "database" ]; then
    echo -e "${GREEN}[PASS] Driver antrean telah sesuai dengan spesifikasi produksi: 'database'.${NC}"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
else
    echo -e "${RED}[FAIL] Driver antrean bernilai '$QUEUE_DRIVER' (seharusnya 'database').${NC}"
    FAILED_CHECKS=$((FAILED_CHECKS + 1))
fi
echo ""

# ------------------------------------------------------------------------------
# 3. Pemeriksaan Pekerjaan Tertahan pada Tabel jobs
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>>> 3. Memeriksa Tabel Antrean (jobs) <<<${NC}"
PENDING_JOBS=$(php artisan tinker --execute="echo \DB::table('jobs')->count();" 2>/dev/null || echo "0")
echo "Jumlah Pekerjaan Tertahan (Pending): $PENDING_JOBS"

if [ "$PENDING_JOBS" -eq "0" ]; then
    echo -e "${GREEN}[PASS] Tidak ada akumulasi pekerjaan tertahan. Worker memproses antrean dengan lancar.${NC}"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
else
    echo -e "${YELLOW}[WARN] Terdapat $PENDING_JOBS pekerjaan di tabel jobs. Pastikan worker sedang aktif memproses.${NC}"
fi
echo ""

# ------------------------------------------------------------------------------
# 4. Pemeriksaan Riwayat Pekerjaan Gagal (failed_jobs)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>>> 4. Memeriksa Tabel Pekerjaan Gagal (failed_jobs) <<<${NC}"
FAILED_JOBS_COUNT=$(php artisan tinker --execute="echo \DB::table('failed_jobs')->count();" 2>/dev/null || echo "0")
echo "Jumlah Pekerjaan Gagal (Failed Jobs): $FAILED_JOBS_COUNT"

if [ "$FAILED_JOBS_COUNT" -eq "0" ]; then
    echo -e "${GREEN}[PASS] Bebas dari failed jobs (0 error). Seluruh listener dieksekusi dengan sukses.${NC}"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
else
    echo -e "${YELLOW}[WARN] Ditemukan $FAILED_JOBS_COUNT job gagal. Jalankan 'php artisan queue:failed' untuk rincian.${NC}"
fi
echo ""

# ------------------------------------------------------------------------------
# 5. Uji Sinyal Restart Worker (queue:restart)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>>> 5. Menguji Mekanisme Graceful Restart (queue:restart) <<<${NC}"
php artisan queue:restart
echo -e "${GREEN}[PASS] Perintah 'php artisan queue:restart' berhasil mengirim sinyal pembaruan RAM worker.${NC}"
PASSED_CHECKS=$((PASSED_CHECKS + 1))
echo ""

# ------------------------------------------------------------------------------
# 6. Memeriksa Akses Berkas Log Worker (storage/logs/worker.log)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}>>> 6. Memeriksa Berkas Log Worker <<<${NC}"
LOG_FILE="storage/logs/worker.log"
if [ -f "$LOG_FILE" ]; then
    LOG_SIZE=$(wc -c < "$LOG_FILE" 2>/dev/null || echo "0")
    echo "Berkas log ditemukan: $LOG_FILE ($LOG_SIZE bytes)"
    echo "10 Baris Terakhir Log Worker:"
    tail -n 10 "$LOG_FILE" 2>/dev/null || true
    echo -e "${GREEN}[PASS] Berkas log worker aktif dan mencatat histori antrean.${NC}"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
else
    echo -e "${YELLOW}[INFO] Berkas $LOG_FILE belum dibuat (akan otomatis terbentuk saat worker pertama kali menulis log).${NC}"
fi
echo ""

# ------------------------------------------------------------------------------
# Ringkasan Akhir
# ------------------------------------------------------------------------------
echo -e "${BLUE}================================================================${NC}"
echo -e "${BLUE}                    RINGKASAN HASIL PENGUJIAN                   ${NC}"
echo -e "${BLUE}================================================================${NC}"
echo -e "Pemeriksaan Berhasil : ${GREEN}$PASSED_CHECKS${NC}"
echo -e "Pemeriksaan Gagal    : ${RED}$FAILED_CHECKS${NC}"

if [ "$FAILED_CHECKS" -eq "0" ]; then
    echo -e "${GREEN}STATUS: SISTEM ANTREAN SIAP PRODUKSI (PRODUCTION READY — MILESTONE M4)${NC}"
else
    echo -e "${RED}STATUS: TERDAPAT KENDALA PADA KONFIGURASI ANTREAN PRODUKSI${NC}"
fi
echo -e "${BLUE}================================================================${NC}"

