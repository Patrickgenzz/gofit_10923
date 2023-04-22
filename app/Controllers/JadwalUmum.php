<?php

namespace App\Controllers;

class JadwalUmum extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM jadwal_umum');
        $result = $query->getResultArray();
       
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getPost();

        $validation = \Config\Services::validation();

        $validation->setRules([
            'ID_KELAS' => 'required',
            'ID_INSTRUKTUR' => 'required',
            'HARI_JADWAL_UMUM' => 'required',
            'SESI_JADWAL_UMUM' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }
        
         //membuat auto increment
         $maxId = $db->table('jadwal_umum')
         ->selectMax('id_jadwal_umum')
         ->get()
         ->getRow()
         ->id_jadwal_umum;
         $newId = $maxId + 1;

        $insertData = [
            'ID_JADWAL_UMUM' => $newId,
            'ID_KELAS' => $data['ID_KELAS'],
            'ID_INSTRUKTUR' => $data['ID_INSTRUKTUR'],
            'HARI_JADWAL_UMUM' => $data['HARI_JADWAL_UMUM'],
            'SESI_JADWAL_UMUM' => $data['SESI_JADWAL_UMUM'],
        ];
        
        $query = $db->table('jadwal_umum')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query_umum = $db->table('jadwal_umum')->delete(['ID_JADWAL_UMUM' => $id]);
        // $query_harian =  $db->table('jadwal_harian')->where('ID_JADWAL_UMUM', $id)->delete();
       
        if ($query_umum ) {
            return $this->respondDeleted(['ID_JADWAL_UMUM' => $id]);
        } else {
            return $this->failServerError();
        }
    }
}