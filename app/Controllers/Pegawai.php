<?php

namespace App\Controllers;

class Pegawai extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM pegawai');
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getJSON();

        $maxIdQuery = $db->query('SELECT MAX(id_pegawai) as max_id FROM pegawai');
        $maxIdResult = $maxIdQuery->getRow();
        $maxId = $maxIdResult->max_id;

        $lastNumber = (int) substr($maxId, 2);
        $newNumber = $lastNumber + 1;

        $newId = 'PI' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        $insertData = [
            'ID_PEGAWAI' => $newId,
            'NAMA_PEGAWAI' => $data->NAMA_PEGAWAI,
            'ALAMAT_PEGAWAI' => $data->ALAMAT_PEGAWAI,
            'TANGGAL_LAHIR_PEGAWAI' =>$data->TANGGAL_LAHIR_PEGAWAI,
            'NO_TELEPON_PEGAWAI' => $data->NO_TELEPON_PEGAWAI,
            'ROLE' => $data->ROLE,
            'EMAIL' => $data->EMAIL,
            'PASSWORD' => password_hash($data->TANGGAL_LAHIR_PEGAWAI, PASSWORD_DEFAULT),
        ];
        
        $query = $db->table('pegawai')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('pegawai')->delete(['ID_PEGAWAI' => $id]);
        if ($query) {
            return $this->respondDeleted(['id' => $id]);
        } else {
            return $this->failServerError();
        }
    }
}