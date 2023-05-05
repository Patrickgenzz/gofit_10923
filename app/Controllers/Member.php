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
        $data = $this->request->getJSON();

        // Get the maximum id from the table and generate a new id
        $newId = $this->generateNewId();

        $insertData = [
            'ID_MEMBER' => $newId,
            'NAMA_MEMBER' => $data->NAMA_MEMBER,
            'ALAMAT_MEMBER' => $data->ALAMAT_MEMBER,
            'TANGGAL_LAHIR_MEMBER' =>$data->TANGGAL_LAHIR_MEMBER,
            'NO_TELEPON_MEMBER' => $data->NO_TELEPON_MEMBER,
            'SISA_DEPOSIT_UANG' => 0,
            'TANGGAL_KADALUARSA' => null,
            'STATUS' => "Tidak Aktif",
            'EMAIL' => $data->EMAIL,
            'PASSWORD' => password_hash($data->TANGGAL_LAHIR_MEMBER, PASSWORD_DEFAULT),
        ];

        $query = $db->table('member')->insert($insertData);
        
        if ($query) {
            return $this->respond($data, 200);;
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

    public function putUpdate($id = null)
    {
        $db = db_connect();
        $data = $this->request->getJSON();

        $updateData = [
            'NAMA_MEMBER' => $data->NAMA_MEMBER,
            'ALAMAT_MEMBER' => $data->ALAMAT_MEMBER,
            'TANGGAL_LAHIR_MEMBER' => $data->TANGGAL_LAHIR_MEMBER,
            'NO_TELEPON_MEMBER' => $data->NO_TELEPON_MEMBER,
        ];

        $query = $db->table('member')->update($updateData, ['ID_MEMBER' => $id]);
        if ($query) {
            return $this->respondUpdated($data, 200);
        } else {
            return $this->failServerError();
        }
    }

    public function getFind($nama = null)
    {
        $db = db_connect();
        $query = $db->table('member')->like('NAMA_MEMBER', $nama)->get();

        $result = $query->getResultArray();

        if ($result) {
            return $this->respond($result, 200);
        } else {
            return $this->failNotFound();
        }
    }

    public function putResetPassword($id = null)
    {
        $db = db_connect();
        $data = $this->request->getJSON();

        $updateData = [
            'PASSWORD' => password_hash(date("Y-m-d"), PASSWORD_DEFAULT),
        ];

        $query = $db->table('member')->update($updateData, ['ID_MEMBER' => $id]);
        if ($query) {
            return $this->respondUpdated($data, 200);
        } else {
            return $this->failServerError();
        }
    }

    private function generateNewId()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_member) as max_id FROM member');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? 'M000', -3);
        $newNumber = $lastNumber + 1;

        $year = date('y');
        $month = date('m');
        
        return $year.'.'.$month.'.' . 'M' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}