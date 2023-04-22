<?php

namespace App\Controllers;

class Kelas extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM kelas');
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getPost();

        $validation = \Config\Services::validation();

        $validation->setRules([
            'JENIS_KELAS' => 'required',
            'TARIF_KELAS' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }

        //membuat auto increment
        $maxId = $db->table('kelas')
        ->selectMax('id_kelas')
        ->get()
        ->getRow()
        ->id_kelas;
        $newId = $maxId + 1;

        $insertData = [
            'ID_KELAS' => $newId,
            'JENIS_KELAS' => $data['JENIS_KELAS'],
            'TARIF_KELAS' => $data['TARIF_KELAS'],
        ];
        
        $query = $db->table('kelas')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('kelas')->delete(['ID_KELAS' => $id]);

        if ($query) {
            return $this->respondDeleted(['ID_KELAS' => $id]);
        } else {
            return $this->failServerError();
        }
    }
}