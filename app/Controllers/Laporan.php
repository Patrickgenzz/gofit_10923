<?php

namespace App\Controllers;

class Laporan extends BaseController
{
    public function getLaporanPendapatanBulanan(){
        $db = db_connect();
        
        $query = $db->query('SELECT months.BULAN, COALESCE(SUM(JUMLAH_PEMBAYARAN), 0) AS JUMLAH_AKTIVASI, COALESCE(SUM(JUMLAH_DEPOSIT), 0) AS JUMLAH_DEPOSIT, COALESCE(SUM(TOTAL), 0) AS TOTAL
                FROM (
                    SELECT 1 AS BULAN UNION ALL
                    SELECT 2 AS BULAN UNION ALL
                    SELECT 3 AS BULAN UNION ALL
                    SELECT 4 AS BULAN UNION ALL
                    SELECT 5 AS BULAN UNION ALL
                    SELECT 6 AS BULAN UNION ALL
                    SELECT 7 AS BULAN UNION ALL
                    SELECT 8 AS BULAN UNION ALL
                    SELECT 9 AS BULAN UNION ALL
                    SELECT 10 AS BULAN UNION ALL
                    SELECT 11 AS BULAN UNION ALL
                    SELECT 12 AS BULAN
                ) AS months
                LEFT JOIN (
                    SELECT MONTH(TANGGAL_AKTIVASI) AS BULAN, JUMLAH_PEMBAYARAN, 0 AS JUMLAH_DEPOSIT, JUMLAH_PEMBAYARAN AS TOTAL
                    FROM transaksi_aktivasi
                    UNION ALL
                    SELECT MONTH(TANGGAL_DEPOSIT_UANG) AS BULAN, 0 AS JUMLAH_PEMBAYARAN, JUMLAH_DEPOSIT_UANG AS JUMLAH_DEPOSIT, JUMLAH_DEPOSIT_UANG AS TOTAL
                    FROM transaksi_deposit_uang
                    UNION ALL
                    SELECT MONTH(TANGGAL_DEPOSIT_KELAS) AS BULAN, 0 AS JUMLAH_PEMBAYARAN, JUMLAH_PEMBAYARAN AS JUMLAH_DEPOSIT, JUMLAH_PEMBAYARAN AS TOTAL
                    FROM transaksi_deposit_kelas
                ) AS combined_table ON months.BULAN = combined_table.BULAN
                GROUP BY months.BULAN');

        $result = $query->getResultArray();

        return $this->respond($result, 200);
    }

    public function getLaporanAktivitasKelas(){
        $db = db_connect();

        $query = $db->query('SELECT k.JENIS_KELAS, i.NAMA_INSTRUKTUR, COUNT(DISTINCT pbk.ID_BOOKING_KELAS) AS JUMLAH_MEMBER,  COUNT(DISTINCT ii.ID_IZIN_INSTRUKTUR) AS JUMLAH_LIBUR
                FROM jadwal_harian jh
                JOIN jadwal_umum ju ON jh.ID_JADWAL_UMUM = ju.ID_JADWAL_UMUM
                JOIN kelas k ON ju.ID_KELAS = k.ID_KELAS
                RIGHT JOIN instruktur i ON jh.ID_INSTRUKTUR = i.ID_INSTRUKTUR
                LEFT JOIN izin_instruktur ii ON jh.TANGGAL_JADWAL_HARIAN = ii.TANGGAL_IZIN
                LEFT JOIN presensi_booking_kelas pbk ON jh.TANGGAL_JADWAL_HARIAN = pbk.TANGGAL_DIBOOKING_KELAS
                GROUP BY k.JENIS_KELAS, i.NAMA_INSTRUKTUR
                ORDER BY k.JENIS_KELAS ASC');

        $result = $query->getResultArray();

        return $this->respond($result, 200);
    }

    public function getLaporanAktivitasGym(){
        $db = db_connect();
        
        $query = $db->query('SELECT DATE(TANGGAL_DIBOOKING_GYM) AS TANGGAL, COUNT(DISTINCT ID_BOOKING_GYM) AS JUMLAH_MEMBER
                FROM presensi_booking_gym
                GROUP BY DATE(TANGGAL_DIBOOKING_GYM)
                ORDER BY TANGGAL ASC');

        $result = $query->getResultArray();

        return $this->respond($result, 200);
    }

    public function getLaporanKinerja(){
        $db = db_connect();

        $query = $db->query('SELECT i.NAMA_INSTRUKTUR,COUNT(DISTINCT pi.ID_PRESENSI_INSTRUKTUR) AS JUMLAH_HADIR,COUNT(DISTINCT ii.ID_IZIN_INSTRUKTUR) AS JUMLAH_LIBUR,COALESCE(late.JUMLAH_TERLAMBAT, 0) AS JUMLAH_TERLAMBAT
            FROM instruktur i
            LEFT JOIN presensi_instruktur pi ON pi.ID_INSTRUKTUR = i.ID_INSTRUKTUR
            LEFT JOIN (SELECT ID_INSTRUKTUR, SUM(KETERLAMBATAN) AS JUMLAH_TERLAMBAT
                    FROM presensi_instruktur
                    GROUP BY ID_INSTRUKTUR) AS late ON i.ID_INSTRUKTUR = late.ID_INSTRUKTUR
            LEFT JOIN izin_instruktur ii ON i.ID_INSTRUKTUR = ii.ID_INSTRUKTUR
            GROUP BY i.ID_INSTRUKTUR, i.NAMA_INSTRUKTUR
            ORDER BY JUMLAH_TERLAMBAT ASC');

        $result = $query->getResultArray();

        return $this->respond($result, 200);
    }

    public function getFindLaporanKinerja($tahun = null, $bulan = null){
        $db = db_connect();
        // $data = $this->request->getJSON();

        $query = $db->query('SELECT i.NAMA_INSTRUKTUR,
                COUNT(DISTINCT pi.ID_PRESENSI_INSTRUKTUR) AS JUMLAH_HADIR,
                COUNT(DISTINCT ii.ID_IZIN_INSTRUKTUR) AS JUMLAH_LIBUR,
                COALESCE(late.JUMLAH_TERLAMBAT, 0) AS JUMLAH_TERLAMBAT
            FROM instruktur i
            LEFT JOIN presensi_instruktur pi ON pi.ID_INSTRUKTUR = i.ID_INSTRUKTUR
            LEFT JOIN (
                SELECT ID_INSTRUKTUR, SUM(KETERLAMBATAN) AS JUMLAH_TERLAMBAT
                FROM presensi_instruktur
                GROUP BY ID_INSTRUKTUR
            ) AS late ON i.ID_INSTRUKTUR = late.ID_INSTRUKTUR
            LEFT JOIN izin_instruktur ii ON i.ID_INSTRUKTUR = ii.ID_INSTRUKTUR
            WHERE YEAR(pi.TANGGAL_JADWAL_HARIAN) = "'.$tahun.'"
                AND MONTH(pi.TANGGAL_JADWAL_HARIAN) = "'.$bulan.'"
            GROUP BY i.ID_INSTRUKTUR, i.NAMA_INSTRUKTUR
            ORDER BY JUMLAH_TERLAMBAT ASC');

        $result = $query->getResultArray();

        if ($result) {
            return $this->respond($result, 200);
        } else {
            return $this->failNotFound();
        }
    }

    public function getFindLaporanAktivitasGym($tahun = null, $bulan = null) {
        $db = db_connect();
        // $data = $this->request->getJSON();

        $query = $db->query('SELECT DATE(TANGGAL_DIBOOKING_GYM) AS TANGGAL, COUNT(DISTINCT ID_BOOKING_GYM) AS JUMLAH_MEMBER
                FROM presensi_booking_gym
                WHERE YEAR(TANGGAL_DIBOOKING_GYM) = "'.$tahun.'" AND MONTH(TANGGAL_DIBOOKING_GYM) = "'.$bulan.'"
                GROUP BY DATE(TANGGAL_DIBOOKING_GYM)
                ORDER BY TANGGAL ASC');
    
        $result = $query->getResultArray();
    
        if ($result) {
            return $this->respond($result, 200);
        } else {
            return $this->failNotFound();
        }
    }

    public function getFindLaporanAktivitasKelas($tahun = null, $bulan = null){
        $db = db_connect();
        // $data = $this->request->getJSON();

        $query = $db->query('SELECT k.JENIS_KELAS, i.NAMA_INSTRUKTUR, COUNT(DISTINCT pbk.ID_BOOKING_KELAS) AS JUMLAH_MEMBER,  COUNT(DISTINCT ii.ID_IZIN_INSTRUKTUR) AS JUMLAH_LIBUR
                FROM jadwal_harian jh
                JOIN jadwal_umum ju ON jh.ID_JADWAL_UMUM = ju.ID_JADWAL_UMUM
                JOIN kelas k ON ju.ID_KELAS = k.ID_KELAS
                RIGHT JOIN instruktur i ON jh.ID_INSTRUKTUR = i.ID_INSTRUKTUR
                LEFT JOIN izin_instruktur ii ON jh.TANGGAL_JADWAL_HARIAN = ii.TANGGAL_IZIN
                LEFT JOIN presensi_booking_kelas pbk ON jh.TANGGAL_JADWAL_HARIAN = pbk.TANGGAL_DIBOOKING_KELAS
                WHERE YEAR(jh.TANGGAL_JADWAL_HARIAN) = "'.$tahun.'" AND MONTH(jh.TANGGAL_JADWAL_HARIAN) = "'.$bulan.'"
                GROUP BY k.JENIS_KELAS, i.NAMA_INSTRUKTUR
                ORDER BY k.JENIS_KELAS ASC');

        $result = $query->getResultArray();

        if ($result) {
            return $this->respond($result, 200);
        } else {
            return $this->failNotFound();
        }
    }

    public function getFindLaporanPendapatanBulanan($tahun = null){
        $db = db_connect();
        // $data = $this->request->getJSON();
        
        $query = $db->query('SELECT months.BULAN, COALESCE(SUM(JUMLAH_PEMBAYARAN), 0) AS JUMLAH_AKTIVASI, COALESCE(SUM(JUMLAH_DEPOSIT), 0) AS JUMLAH_DEPOSIT, COALESCE(SUM(TOTAL), 0) AS TOTAL
                FROM (
                    SELECT 1 AS BULAN UNION ALL
                    SELECT 2 AS BULAN UNION ALL
                    SELECT 3 AS BULAN UNION ALL
                    SELECT 4 AS BULAN UNION ALL
                    SELECT 5 AS BULAN UNION ALL
                    SELECT 6 AS BULAN UNION ALL
                    SELECT 7 AS BULAN UNION ALL
                    SELECT 8 AS BULAN UNION ALL
                    SELECT 9 AS BULAN UNION ALL
                    SELECT 10 AS BULAN UNION ALL
                    SELECT 11 AS BULAN UNION ALL
                    SELECT 12 AS BULAN
                ) AS months
                LEFT JOIN (
                    SELECT MONTH(TANGGAL_AKTIVASI) AS BULAN, JUMLAH_PEMBAYARAN, 0 AS JUMLAH_DEPOSIT, JUMLAH_PEMBAYARAN AS TOTAL
                    FROM transaksi_aktivasi
                    WHERE YEAR(TANGGAL_AKTIVASI) = "'.$tahun.'"
                    UNION ALL
                    SELECT MONTH(TANGGAL_DEPOSIT_UANG) AS BULAN, 0 AS JUMLAH_PEMBAYARAN, JUMLAH_DEPOSIT_UANG AS JUMLAH_DEPOSIT, JUMLAH_DEPOSIT_UANG AS TOTAL
                    FROM transaksi_deposit_uang
                    WHERE YEAR(TANGGAL_DEPOSIT_UANG) = "'.$tahun.'"
                    UNION ALL
                    SELECT MONTH(TANGGAL_DEPOSIT_KELAS) AS BULAN, 0 AS JUMLAH_PEMBAYARAN, JUMLAH_PEMBAYARAN AS JUMLAH_DEPOSIT, JUMLAH_PEMBAYARAN AS TOTAL
                    FROM transaksi_deposit_kelas
                    WHERE YEAR(TANGGAL_DEPOSIT_KELAS) = "'.$tahun.'"
                ) AS combined_table ON months.BULAN = combined_table.BULAN
                GROUP BY months.BULAN');

        $result = $query->getResultArray();

        return $this->respond($result, 200);
    }
}