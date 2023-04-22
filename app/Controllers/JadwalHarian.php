<?php

namespace App\Controllers;

class JadwalHarian extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM jadwal_harian');
        $result = $query->getResultArray();
       
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getPost();

        $validation = \Config\Services::validation();

        $validation->setRules([
            'ID_JADWAL_UMUM' => 'required',
            'ID_INSTRUKTUR' => 'required',
            'STATUS_KELAS' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }
        
        $insertData = [
            'TANGGAL_JADWAL_HARIAN' => date("Y-m-d"),
            'ID_JADWAL_UMUM' => $data['ID_JADWAL_UMUM'],
            'ID_INSTRUKTUR' => $data['ID_INSTRUKTUR'],
            'STATUS_KELAS' => $data['STATUS_KELAS'],
        ];
        
        $query = $db->table('jadwal_harian')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    // public function deleteDelete($id = null)
    // {
    //     $db = db_connect();
    //     $query = $db->table('jadwal_harian')->delete(['ID_JADWAL_UMUM' => $id]);
    //     if ($query) {
    //         return $this->respondDeleted(['ID_JADWAL_UMUM' => $id]);
    //     } else {
    //         return $this->failServerError();
    //     }
    // }
}