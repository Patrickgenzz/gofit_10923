<?php

namespace App\Controllers;

class PresensiInstruktur extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM presensi_instruktur');
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getPost();

        $validation = \Config\Services::validation();

        $validation->setRules([
            'ID_INSTRUKTUR' => 'required',
            'TANGGAL_JADWAL_HARIAN' => 'required',
            'WAKTU_MULAI_KELAS' => 'required',
            'WAKTU_SELESAI_KELAS' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }

        //membuat auto increment
        $maxId = $db->table('presensi_instruktur')
        ->selectMax('id_presensi_instruktur')
        ->get()
        ->getRow()
        ->id_presensi_instruktur;
        $newId = $maxId + 1;

        $insertData = [
            'ID_PRESENSI_INSTRUKTUR' => $newId,
            'ID_INSTRUKTUR' => $data['ID_INSTRUKTUR'],
            'TANGGAL_JADWAL_HARIAN' =>  date("Y-m-d"),
            'WAKTU_MULAI_KELAS' => Time('H:i:s'),// harusnya pake data
            'WAKTU_SELESAI_KELAS' => Time('H:i:s'),
            'DURASI_KELAS' => $data['DURASI_KELAS'], //harusnya jumlah dri waktu selesai - waktu mulai
            'KETERLAMBATAN' => $data['DURASI_KELAS'],
        ];
        
        $query = $db->table('presensi_instruktur')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function delete($id = null)
    {
        $db = db_connect();
        $data = $db->table('presensi_instruktur')->delete(['ID_PRESENSI_INSTRUKTUR' => $id]);
        if ($data) {
            return $this->respondDeleted($data);
        } else {
            return $this->failServerError();
        }
    }
}