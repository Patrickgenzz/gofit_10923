<?php

namespace App\Controllers;

class PresensiBookingKelas extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM presensi_booking_kelas');
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
            'TANGGAL_BOOKING_KELAS' => 'required',
            'TANGGAL_DIBOOKING_KELAS' => 'required',
            'WAKTU_PRESENSI_KELAS' => 'required',
            'TARIF_KELAS' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }

        //membuat auto increment
        $maxId = $db->table('presensi_booking_kelas')
        ->selectMax('id_booking_kelas')
        ->get()
        ->getRow()
        ->id_booking_kelas;
        $newId = $maxId + 1;

        $insertData = [
            'ID_BOOKING_KELAS' => $newId,
            'ID_MEMBER' => $data['ID_MEMBER'],
            'TANGGAL_BOOKING_KELAS' =>  date("Y-m-d"),
            'TANGGAL_DIBOOKING_KELAS' => date("Y-m-d"),// harusnya pake data
            'WAKTU_PRESENSI_KELAS' => null,
            'TARIF_KELAS' => $data['TARIF_KELAS'],
        ];
        
        $query = $db->table('presensi_booking_kelas')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('presensi_booking_kelas')->delete(['ID_BOOKING_KELAS' => $id]);
        if ($query) {
            return $this->respondDeleted(['id_booking_kelas' => $id]);
        } else {
            return $this->failServerError();
        }
    }
}