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
        $data = $this->request->getJSON();

        $maxIdQuery = $db->query('SELECT MAX(id_kelas) as max_id FROM kelas');
        $maxIdResult = $maxIdQuery->getRow();
        $maxId = $maxIdResult->max_id;

        $lastNumber = (int) substr($maxId, 2);
        $newNumber = $lastNumber + 1;

        $newId = 'KS' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        $insertData = [
            'ID_KELAS' => $newId,
            'JENIS_KELAS' => $data->JENIS_KELAS,
            'TARIF_KELAS' => $data->TARIF_KELAS,
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