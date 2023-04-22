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
        $data = $this->request->getPost();

        $validation = \Config\Services::validation();

        $validation->setRules([
            'NAMA_PEGAWAI' => 'required',
            'ALAMAT_PEGAWAI' => 'required',
            'TANGGAL_LAHIR_PEGAWAI' => 'required',
            'NO_TELEPON_PEGAWAI' => 'required',
            'ROLE' => 'required',
            'PASSWORD' => 'required',
            'EMAIL' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }

        //membuat auto increment
        $maxId = $db->table('pegawai')
        ->selectMax('id_pegawai')
        ->get()
        ->getRow()
        ->id_pegawai;
        $newId = $maxId + 1;

        $insertData = [
            'ID_PEGAWAI' => $newId,
            'NAMA_PEGAWAI' => $data['NAMA_PEGAWAI'],
            'ALAMAT_PEGAWAI' => $data['ALAMAT_PEGAWAI'],
            'TANGGAL_LAHIR_PEGAWAI' => date("Y-m-d"),//harusnya pake data
            'NO_TELEPON_PEGAWAI' => $data['NO_TELEPON_PEGAWAI'],
            'ROLE' => $data['ROLE'],
            'EMAIL' => $data['EMAIL'],
            'PASSWORD' => password_hash($data['PASSWORD'], PASSWORD_DEFAULT),
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