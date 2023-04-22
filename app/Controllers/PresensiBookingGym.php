<?php

namespace App\Controllers;

class PresensiBookingGym extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM presensi_booking_gym');
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
            'TANGGAL_BOOKING_GYM' => 'required',
            'TANGGAL_DIBOOKING_GYM' => 'required',
            'TANGGAL_PRESENSI_GYM' => 'required',
            'SLOT_WAKTU_GYM' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }

        //membuat auto increment
        $maxId = $db->table('presensi_booking_gym')
        ->selectMax('id_booking_gym')
        ->get()
        ->getRow()
        ->id_booking_gym;
        $newId = $maxId + 1;

        $insertData = [
            'ID_BOOKING_GYM' => $newId,
            'ID_MEMBER' => $data['ID_MEMBER'],
            'TANGGAL_BOOKING_GYM' =>  date("Y-m-d"),
            'TANGGAL_DIBOOKING_GYM' => date("Y-m-d"),// harusnya pake data
            'TANGGAL_PRESENSI_GYM' => null,
            'SLOT_WAKTU_GYM' => $data['SLOT_WAKTU_GYM'],
        ];
        
        $query = $db->table('presensi_booking_gym')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('presensi_booking_gym')->delete(['ID_BOOKING_GYM' => $id]);
        if ($query) {
            return $this->respondDeleted(['id' => $id]);
        } else {
            return $this->failServerError();
        }
    }
}