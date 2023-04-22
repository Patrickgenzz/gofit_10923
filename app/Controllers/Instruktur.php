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

        //membuat auto increment
        $maxId = $db->table('instruktur')
        ->selectMax('id_instruktur')
        ->get()
        ->getRow()
        ->id_instruktur;
        $newId = $maxId + 1;

        $insertData = [
            'ID_INSTRUKTUR' => $newId,
            'NAMA_INSTRUKTUR' => $data->NAMA_INSTRUKTUR,
            'ALAMAT_INSTRUKTUR' => $data->ALAMAT_INSTRUKTUR,
            'TANGGAL_LAHIR_INSTRUKTUR' => $data->TANGGAL_LAHIR_INSTRUKTUR,
            'NO_TELEPON_INSTRUKTUR' => $data->NO_TELEPON_INSTRUKTUR,
            'EMAIL' => $data->EMAIL,
            'PASSWORD' => password_hash($data->PASSWORD, PASSWORD_DEFAULT),
        ];
        
        $query = $db->table('instruktur')->insert($insertData);
        
        if ($query) {
            return $this->respond($data, 200);;
        } else {
            return $this->failServerError();
        }
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
}