<?php

namespace App\Controllers;

class DepositKelasMember extends BaseController
{
    public function getIndex()
    {
        $query = $this->$db->query('SELECT * FROM deposit_kelas_member');
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getPost();

        $validation = \Config\Services::validation();

        $validation->setRules([
            'ID_MEMBER' => 'required',
            'ID_KELAS' => 'required',
            'NAMA' => 'required',
            'NO_TELP' => 'required',
            'JENIS' => 'required',
            'MINIMAL_PEMBAYARAN' => 'required'
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }

        //membuat auto increment
        $maxId = $db->table('deposit_kelas_member')
        ->selectMax('id_deposit_kelas_member')
        ->get()
        ->getRow()
        ->id_deposit_kelas_member;
        $newId = $maxId + 1;
        
        $insertData = [
            'ID_DEPOSIT_KELAS_MEMBER' => $newId,
            'ID_MEMBER' => $data['ID_MEMBER'],
            'ID_KELAS' => $data['ID_KELAS'],
            'NAMA' => $data['NAMA'],
            'NO_TELP' => $data['NO_TELP'],
            'JENIS' => $data['JENIS'],
            'MINIMAL_PEMBAYARAN' => $data['MINIMAL_PEMBAYARAN']
        ];
        
        $query = $db->table('deposit_kelas_member')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('deposit_kelas_member')->delete(['ID_DEPOSIT_KELAS_MEMBER' => $id]);
        
        if ($query) {
            return $this->respondDeleted(['ID_DEPOSIT_KELAS_MEMBER' => $id]);
        } else {
            return $this->failServerError();
        }
    }
}