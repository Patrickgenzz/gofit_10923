<?php

namespace App\Controllers;

class Instruktur extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM instruktur');
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function getInstrukturById($id = null){
        $db = db_connect();
        $query = $db->table('instruktur')->where('ID_INSTRUKTUR', $id)->get();
        $result = $query->getRow(); 
        
        if ($result) {
            $terlambat = $db->query('SELECT SUM(keterlambatan) AS TOTAL_KETERLAMBATAN
                FROM presensi_instruktur
                WHERE id_instruktur = "'.$id.'"')->getRow();

            if(!$terlambat->TOTAL_KETERLAMBATAN){
                $terlambat->TOTAL_KETERLAMBATAN = 0;
            }

            $result->TOTAL_KETERLAMBATAN = $terlambat->TOTAL_KETERLAMBATAN;
            return $this->respond($result, 200);
        } else {
            return $this->failNotFound('Instruktur Tidak Ditemukan!');
        }
    }

    public function getJadwalInstrukturById($id = null){
        $db = db_connect();

        $query = $db->query('SELECT * FROM jadwal_harian WHERE ID_INSTRUKTUR = "'.$id.'" 
                    AND tanggal_jadwal_harian >= CURDATE()');

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

        // Get the maximum id from the table and generate a new id
        $newId = $this->generateNewId();
    
        $insertData = [
            'ID_INSTRUKTUR' => $newId,
            'NAMA_INSTRUKTUR' => $data->NAMA_INSTRUKTUR,
            'ALAMAT_INSTRUKTUR' => $data->ALAMAT_INSTRUKTUR,
            'TANGGAL_LAHIR_INSTRUKTUR' => $data->TANGGAL_LAHIR_INSTRUKTUR,
            'NO_TELEPON_INSTRUKTUR' => $data->NO_TELEPON_INSTRUKTUR,
            'EMAIL' => $data->EMAIL,
            'PASSWORD' => password_hash($data->TANGGAL_LAHIR_INSTRUKTUR, PASSWORD_DEFAULT),
        ];
        
        $query = $db->table('instruktur')->insert($insertData);
        
        if ($query) {
            return $this->respond($insertData, 200);
        } else {
            return $this->failServerError();
        }
    }

    private function generateNewId()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_instruktur) as max_id FROM instruktur');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? 'I000', -3);
        $newNumber = $lastNumber + 1;

        return 'I' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('instruktur')->delete(['ID_INSTRUKTUR' => $id]);
        
        if ($query) {
            return $this->respondDeleted(['id' => $id]);
        } else {
            return $this->failServerError();
        }
    }

    public function putUpdate($id = null)
    {
        $db = db_connect();
        $data = $this->request->getJSON();

        $updateData = [
            'NAMA_INSTRUKTUR' => $data->NAMA_INSTRUKTUR,
            'ALAMAT_INSTRUKTUR' => $data->ALAMAT_INSTRUKTUR,
            'TANGGAL_LAHIR_INSTRUKTUR' => $data->TANGGAL_LAHIR_INSTRUKTUR,
            'NO_TELEPON_INSTRUKTUR' => $data->NO_TELEPON_INSTRUKTUR,
        ];

        $query = $db->table('instruktur')->update($updateData, ['ID_INSTRUKTUR' => $id]);
        if ($query) {
            return $this->respondUpdated($data, 200);
        } else {
            return $this->failServerError();
        }
    }

    public function getFind($nama = null)
    {
        $db = db_connect();
        $query = $db->table('instruktur')->like('NAMA_INSTRUKTUR', $nama)->get();

        $result = $query->getResultArray();

        if ($result) {
            return $this->respond($result, 200);
        } else {
            return $this->failNotFound();
        }
    }

    //make function reset password
    public function putResetPassword($id = null)
    {
        $db = db_connect();
        $data = $this->request->getJSON();

        $updateData = [
            'PASSWORD' => password_hash($data->TANGGAL_LAHIR_INSTRUKTUR, PASSWORD_DEFAULT),
        ];

        $query = $db->table('instruktur')->update($updateData, ['ID_INSTRUKTUR' => $id]);
        if ($query) {
            return $this->respondUpdated($data, 200);
        } else {
            return $this->failServerError();
        }
    }

    public function getListPresensiInstruktur($id = null){
        $db = db_connect();

        $query = $db->query('SELECT * FROM presensi_instruktur WHERE ID_INSTRUKTUR = "'.$id.'"');

        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
        return $this->respond($response, 200);
    }

    public function getListInstruktur($id = null)
    {
        $db = db_connect();
    
        $query = $db->query('SELECT i.NAMA_INSTRUKTUR
            FROM instruktur i
            JOIN jadwal_harian jh ON i.ID_INSTRUKTUR = jh.ID_INSTRUKTUR
            JOIN jadwal_umum ju ON ju.ID_JADWAL_UMUM = jh.ID_JADWAL_UMUM
            WHERE i.ID_INSTRUKTUR != "'.$id.'"
            AND (ju.SESI_JADWAL_UMUM, DATE(jh.TANGGAL_JADWAL_HARIAN)) NOT IN (
                SELECT ju2.SESI_JADWAL_UMUM, DATE(jh2.TANGGAL_JADWAL_HARIAN)
                FROM instruktur i2
                JOIN jadwal_harian jh2 ON i2.ID_INSTRUKTUR = jh2.ID_INSTRUKTUR
                JOIN jadwal_umum ju2 ON ju2.ID_JADWAL_UMUM = jh2.ID_JADWAL_UMUM
                WHERE i2.ID_INSTRUKTUR = "'.$id.'"
            )
            AND jh.TANGGAL_JADWAL_HARIAN <= CURDATE()
            GROUP BY i.NAMA_INSTRUKTUR');

        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
        
        return $this->respond($response, 200);
    }
}