<?php

namespace App\Controllers;

class Member extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM member');
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getPost();

        $validation = \Config\Services::validation();

        $validation->setRules([
            'NAMA_MEMBER' => 'required',
            'ALAMAT_MEMBER' => 'required',
            'TANGGAL_LAHIR_MEMBER' => 'required',
            'NO_TELEPON_MEMBER' => 'required',
            'EMAIL' => 'required',
            'PASSWORD' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }

        //membuat auto increment
        $maxId = $db->table('member')
        ->selectMax('id_member')
        ->get()
        ->getRow()
        ->id_member;
        $newId = $maxId + 1;

        $insertData = [
            'ID_MEMBER' => $newId,
            'NAMA_MEMBER' => $data['NAMA_MEMBER'],
            'ALAMAT_MEMBER' => $data['ALAMAT_MEMBER'],
            'TANGGAL_LAHIR_MEMBER' => date("Y-m-d"), // harusnya pake data
            'NO_TELEPON_MEMBER' => $data['NO_TELEPON_MEMBER'],
            'SISA_DEPOSIT_UANG' => 0,
            'TANGGAL_KADALUARSA' => null,
            'STATUS' => null,
            'EMAIL' => $data['EMAIL'],
            'PASSWORD' => password_hash($data['PASSWORD'], PASSWORD_DEFAULT),
        ];
        
        $query = $db->table('member')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('member')->delete(['ID_MEMBER' => $id]);
        if ($query) {
            return $this->respondDeleted(['id' => $id]);
        } else {
            return $this->failServerError();
        }
    }
}