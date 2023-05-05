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
            return $this->respond($data, 200);;
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
}