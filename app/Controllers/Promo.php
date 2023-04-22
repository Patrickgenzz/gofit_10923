<?php

namespace App\Controllers;

class Promo extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM promo');
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getPost();

        $validation = \Config\Services::validation();

        $validation->setRules([
            'JENIS_PROMO' => 'required',
            // 'WAKTU_MULAI_PROMO' => 'required',
            // 'WAKTU_SELESAI_PROMO' => 'required',
            'MINIMAL_PEMBELIAN' => 'required',
            'BONUS' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }

        //membuat auto increment
        $maxId = $db->table('promo')
        ->selectMax('id_promo')
        ->get()
        ->getRow()
        ->id_promo;
        $newId = $maxId + 1;

        $insertData = [
            'ID_PROMO' => $newId,
            'JENIS_PROMO' => $data['JENIS_PROMO'],
            'WAKTU_MULAI_PROMO' =>  date("Y-m-d H:i:s"),
            'WAKTU_SELESAI_PROMO' => date("Y-m-d H:i:s"),// harusnya pake data
            'MINIMAL_PEMBELIAN' => $data['MINIMAL_PEMBELIAN'],
            'BONUS' => $data['BONUS'],
        ];
        
        $query = $db->table('promo')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('promo')->delete(['ID_PROMO' => $id]);
        if ($query) {
            return $this->respondDeleted(['id_promo' => $id]);
        } else {
            return $this->failServerError();
        }
    }

}