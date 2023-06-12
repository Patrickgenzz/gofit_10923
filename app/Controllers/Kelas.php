<?php

namespace App\Controllers;

class Kelas extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM kelas');
                
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function getBookingKelas(){
        $db = db_connect();
    
        $query = $db->query('SELECT k.*, jh.TANGGAL_JADWAL_HARIAN, ju.HARI_JADWAL_UMUM, ju.SESI_JADWAL_UMUM, i.NAMA_INSTRUKTUR
                FROM jadwal_harian jh 
                JOIN instruktur i ON jh.id_instruktur = i.id_instruktur
                JOIN jadwal_umum ju ON jh.id_jadwal_umum = ju.id_jadwal_umum 
                JOIN kelas k ON ju.id_kelas = k.id_kelas
                where jh.tanggal_jadwal_harian >= CURDATE()
                ORDER BY jh.TANGGAL_JADWAL_HARIAN ASC');
                
        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
    
        return $this->respond($response, 200);
    }

    public function getListBookingKelas(){
        $db = db_connect();
    
        $query = $db->query('SELECT pbk.*, ju.HARI_JADWAL_UMUM, ju.SESI_JADWAL_UMUM, k.JENIS_KELAS
                FROM jadwal_harian jh 
                JOIN instruktur i ON jh.id_instruktur = i.id_instruktur
                JOIN jadwal_umum ju ON jh.id_jadwal_umum = ju.id_jadwal_umum 
                JOIN kelas k ON ju.id_kelas = k.id_kelas
                JOIN presensi_booking_kelas pbk ON pbk.tanggal_dibooking_kelas = jh.tanggal_jadwal_harian
                ORDER BY jh.TANGGAL_JADWAL_HARIAN ASC');
                
        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
    
        return $this->respond($response, 200);
    }

    // public function getListKelasHariIni(){
    //     $db = db_connect();
    
    //     $query = $db->query('SELECT k.*, jh.STATUS_KELAS, jh.TANGGAL_JADWAL_HARIAN, ju.HARI_JADWAL_UMUM, ju.SESI_JADWAL_UMUM, i.NAMA_INSTRUKTUR, i.ID_INSTRUKTUR
    //             FROM jadwal_harian jh 
    //             JOIN instruktur i ON jh.id_instruktur = i.id_instruktur
    //             JOIN jadwal_umum ju ON jh.id_jadwal_umum = ju.id_jadwal_umum 
    //             JOIN kelas k ON ju.id_kelas = k.id_kelas
    //             AND jh.STATUS_KELAS != "Libur"
    //             AND jh.TANGGAL_JADWAL_HARIAN >= CURDATE()
    //             AND jh.TANGGAL_JADWAL_HARIAN <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    //             ORDER BY jh.TANGGAL_JADWAL_HARIAN ASC');
                
    //     $result = $query->getResultArray();

    //     $response = [
    //         'data' => $result
    //     ];
    
    //     return $this->respond($response, 200);
    // }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getJSON();

        $maxIdQuery = $db->query('SELECT MAX(id_kelas) as max_id FROM kelas');
        $maxIdResult = $maxIdQuery->getRow();
        $maxId = $maxIdResult->max_id;

        $lastNumber = (int) substr($maxId, 2);
        $newNumber = $lastNumber + 1;

        $newId = 'KS' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        $insertData = [
            'ID_KELAS' => $newId,
            'JENIS_KELAS' => $data->JENIS_KELAS,
            'TARIF_KELAS' => $data->TARIF_KELAS,
            'KUOTA' => 10,
        ];
        
        $query = $db->table('kelas')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('kelas')->delete(['ID_KELAS' => $id]);

        if ($query) {
            return $this->respondDeleted(['ID_KELAS' => $id]);
        } else {
            return $this->failServerError();
        }
    }
}