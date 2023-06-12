<?php

namespace App\Controllers;

class PresensiInstruktur extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();

        $currentDate = date('Y-m-d');

        // Reset jumlah terlambat instruktur
        if (date('d', strtotime($currentDate)) === '01') {
            $db->query('UPDATE presensi_instruktur SET keterlambatan = 0');
        }
    
        $query = $db->query('SELECT * FROM presensi_instruktur');
        
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function getListKelasHariIni(){
        $db = db_connect();
    
        $query = $db->query('SELECT k.*, jh.STATUS_KELAS, jh.TANGGAL_JADWAL_HARIAN, ju.HARI_JADWAL_UMUM, ju.SESI_JADWAL_UMUM, i.NAMA_INSTRUKTUR, i.ID_INSTRUKTUR
                FROM jadwal_harian jh 
                JOIN instruktur i ON jh.id_instruktur = i.id_instruktur
                JOIN jadwal_umum ju ON jh.id_jadwal_umum = ju.id_jadwal_umum 
                JOIN kelas k ON ju.id_kelas = k.id_kelas
                AND jh.STATUS_KELAS != "Libur"
                AND jh.TANGGAL_JADWAL_HARIAN >= CURDATE()
                AND jh.TANGGAL_JADWAL_HARIAN <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                ORDER BY jh.TANGGAL_JADWAL_HARIAN ASC');
                
        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
    
        return $this->respond($response, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getJSON();

        $newId = $this->generateNewId();

        //cek apakah sudah pernah melakukan presensi dengan tanggal yang sama dan id instruktur yang sama
        $cek = $db->query('SELECT * FROM presensi_instruktur 
                        WHERE ID_INSTRUKTUR = "'.$data->ID_INSTRUKTUR.'" 
                        AND TANGGAL_JADWAL_HARIAN = "'.$data->TANGGAL_JADWAL_HARIAN.'"');
        $result = $cek->getResultArray();

        if(count($result) > 0){
            return $this->response->setStatusCode(406);
        }

        $waktuMulai = date('H:i:s', strtotime($data->TANGGAL_JADWAL_HARIAN));
        $waktuSelesai = date('H:i:s', strtotime($waktuMulai . ' + 2 hours'));
        
        $waktuSekarang = time();

        if ($waktuSekarang > strtotime($waktuMulai)) {
            $terlambat = $waktuSekarang - strtotime($waktuMulai);
        } else {
            $terlambat = 0;
        }

        $insertData = [
            'ID_PRESENSI_INSTRUKTUR' => $newId,
            'ID_INSTRUKTUR' => $data->ID_INSTRUKTUR,
            'TANGGAL_JADWAL_HARIAN' =>  $data->TANGGAL_JADWAL_HARIAN,
            'WAKTU_MULAI_KELAS' =>  $waktuMulai,
            'WAKTU_SELESAI_KELAS' => $waktuSelesai,
            'DURASI_KELAS' => 2, 
            'KETERLAMBATAN' => $terlambat,
        ];

        $query = $db->table('presensi_instruktur')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($insertData);
        } else {
            return $this->failServerError();
        }
    }

    public function postUpdate(){
        $db = db_connect();
        $data = $this->request->getJSON();

        $waktuMulai = date('H:i:s', strtotime($data->WAKTU_MULAI_KELAS));
        $waktuSelesai = date('H:i:s', strtotime($waktuMulai . ' + 2 hours'));

        $query = $db->query('UPDATE presensi_instruktur SET 
                        TANGGAL_JADWAL_HARIAN = "'.$data->TANGGAL_JADWAL_HARIAN.'",
                        WAKTU_MULAI_KELAS = "'.$waktuMulai.'",
                        WAKTU_SELESAI_KELAS = "'.$waktuSelesai.'",
                        WHERE ID_PRESENSI_INSTRUKTUR = "'.$data->ID_PRESENSI_INSTRUKTUR.'"');

        $db->query('UPDATE jadwal_harian set tanggal_jadwal_harian = "'.$data->TANGGAL_JADWAL_HARIAN.'"
                    WHERE tanggal_jadwal_harian = "'.$data->TANGGAL_JADWAL_HARIAN.'"');

    }

    public function delete($id = null)
    {
        $db = db_connect();
        $data = $db->table('presensi_instruktur')->delete(['ID_PRESENSI_INSTRUKTUR' => $id]);
        if ($data) {
            return $this->respondDeleted($data);
        } else {
            return $this->failServerError();
        }
    }

    private function generateNewId()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_presensi_instruktur) as max_id FROM presensi_instruktur');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? '000', -3);
        $newNumber = $lastNumber + 1;

        $year = date('y');
        $month = date('m');
        
        return $year.'.'.$month.'.' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}