<?php

namespace App\Controllers;

class JadwalUmum extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();

        $query = $db->query('SELECT ju.*, k.JENIS_KELAS, i.NAMA_INSTRUKTUR
                     FROM jadwal_umum ju
                     JOIN kelas k ON ju.ID_KELAS = k.ID_KELAS
                     JOIN instruktur i ON ju.ID_INSTRUKTUR = i.ID_INSTRUKTUR');

        $result = $query->getResultArray();
       
        return $this->respond($result, 200);
    }

    public function postCreate()
    {
        $data = $this->request->getJSON();

        // Get the maximum id from the table and generate a new id
        $newId = $this->generateNewId();

        // Check if the instructor's schedule conflicts with the new schedule
        if ($this->isScheduleConflict($data->ID_INSTRUKTUR, $data->HARI_JADWAL_UMUM, $data->SESI_JADWAL_UMUM)) {
            return $this->fail('Jadwal Instruktur Bertabrakan!', 400);
        }

        // Insert the new schedule to the database
        $insertData = [
            'ID_JADWAL_UMUM' => $newId,
            'ID_KELAS' => $data->ID_KELAS,
            'ID_INSTRUKTUR' => $data->ID_INSTRUKTUR,
            'HARI_JADWAL_UMUM' => $data->HARI_JADWAL_UMUM,
            'SESI_JADWAL_UMUM' => $data->SESI_JADWAL_UMUM,
        ];
        $query = db_connect()->table('jadwal_umum')->insert($insertData);

        if (!$query)
            return $this->failServerError();

        // Include additional information in the returned data
        $data->JENIS_KELAS = $this->getJenisKelas($data->ID_KELAS);
        $data->NAMA_INSTRUKTUR = $this->getNamaInstruktur($data->ID_INSTRUKTUR);

        return $this->respondCreated($data);
    }

    public function putUpdate($id = null)
    {
        $db = db_connect();
        $data = $this->request->getJSON();

        if ($this->isScheduleConflict($data->ID_INSTRUKTUR, $data->HARI_JADWAL_UMUM, $data->SESI_JADWAL_UMUM)) {
            return $this->fail('Jadwal Instruktur Bertabrakan!', 400);
        }
        
        //update data jadwal umum
        $updateData = [
            'ID_KELAS' => $data->ID_KELAS,
            'ID_INSTRUKTUR' => $data->ID_INSTRUKTUR,
            'HARI_JADWAL_UMUM' => $data->HARI_JADWAL_UMUM,
            'SESI_JADWAL_UMUM' => $data->SESI_JADWAL_UMUM,
        ];
        
        $query = $db->table('jadwal_umum')->update($updateData, ['ID_JADWAL_UMUM' => $id]);
        
        if (!$query) {
            return $this->failServerError();
        }

        // menambahkan informasi tambahan pada data yang dikembalikan
        $data->JENIS_KELAS = $this->getJenisKelas($data->ID_KELAS);
        $data->NAMA_INSTRUKTUR = $this->getNamaInstruktur($data->ID_INSTRUKTUR);

        return $this->respondUpdated($data, 200); 
    }


    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query_harian =  $db->table('jadwal_harian')->where('ID_JADWAL_UMUM', $id)->delete();
        $query_umum = $db->table('jadwal_umum')->delete(['ID_JADWAL_UMUM' => $id]);
       
        if ($query_umum ) {
            return $this->respondDeleted(['ID_JADWAL_UMUM' => $id]);
        } else {
            return $this->failServerError();
        }
    }

    private function generateNewId()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_jadwal_umum) as max_id FROM jadwal_umum');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? 'JU000', -3);
        $newNumber = $lastNumber + 1;

        return 'JU' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    private function isScheduleConflict($instrukturId, $hariJadwal, $sesiJadwal)
    {
        $queryCheckSchedule = db_connect()->query('SELECT * FROM jadwal_umum WHERE ID_INSTRUKTUR = ? AND HARI_JADWAL_UMUM = ? AND SESI_JADWAL_UMUM = ?', [$instrukturId, $hariJadwal, $sesiJadwal]);
        return count($queryCheckSchedule->getResult()) > 0;
    }

    private function getJenisKelas($kelasId)
    {
        $query = db_connect()->query('SELECT JENIS_KELAS FROM kelas WHERE ID_KELAS = ?', [$kelasId]);
        $result = $query->getRow();
        return $result->JENIS_KELAS ?? null;
    }

    private function getNamaInstruktur($instrukturId)
    {
        $query = db_connect()->query('SELECT NAMA_INSTRUKTUR FROM instruktur WHERE ID_INSTRUKTUR = ?', [$instrukturId]);
        $result = $query->getRow();
        return $result->NAMA_INSTRUKTUR ?? null;
    }
}