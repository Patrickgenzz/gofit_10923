<?php

namespace App\Controllers;

class Authentication extends BaseController
{
    public function postLogin()
    {
        $db = db_connect();
        $data = $this->request->getJSON();

        $results = $db->query(
            'SELECT ROLE as USER_TYPE, EMAIL, PASSWORD, NAMA_PEGAWAI as NAMA, ID_PEGAWAI as ID, ALAMAT_PEGAWAI AS ALAMAT, TANGGAL_LAHIR_PEGAWAI AS TANGGAL_LAHIR, NO_TELEPON_PEGAWAI AS NO_TELEPON FROM pegawai WHERE EMAIL="' . $data->EMAIL . '"
            UNION ALL
            SELECT "MEMBER" as USER_TYPE, EMAIL, PASSWORD, NAMA_MEMBER as NAMA, ID_MEMBER as ID, ALAMAT_MEMBER AS ALAMAT, TANGGAL_LAHIR_MEMBER AS TANGGAL_LAHIR, NO_TELEPON_MEMBER AS NO_TELEPON FROM member WHERE EMAIL="' . $data->EMAIL . '"
            UNION ALL
            SELECT "INSTRUKTUR" as USER_TYPE, EMAIL, PASSWORD, NAMA_INSTRUKTUR as NAMA, ID_INSTRUKTUR as ID, ALAMAT_INSTRUKTUR AS ALAMAT, TANGGAL_LAHIR_INSTRUKTUR AS TANGGAL_LAHIR, NO_TELEPON_INSTRUKTUR AS NO_TELEPON FROM instruktur WHERE EMAIL="' . $data->EMAIL . '";'
        )->getResultArray();

        if (count($results) > 0) {
            //cek apakah password sesuai
            if (password_verify($data->PASSWORD, $results[0]['PASSWORD'])) {
                unset($results[0]['PASSWORD']);
                return $this->respond($results[0], 200);
            } else {
                return $this->failUnauthorized();
            }
        } else {
            return $this->failNotFound('Email Tidak Ditemukan!');
        }
    }

    // make ganti password
    public function postGantiPassword()
    {
        $db = db_connect();
        $data = $this->request->getJSON();
        
        $results = $db->query(
            'SELECT EMAIL, PASSWORD FROM pegawai WHERE EMAIL="' . $data->EMAIL . '";'
        )->getResultArray();

        if (password_verify($data->PASSWORD, $results[0]['PASSWORD'])) {
            $updateData = [
                'PASSWORD' => password_hash($data->NEW_PASSWORD, PASSWORD_DEFAULT)
            ];

            $query = $db->table('pegawai')->where('EMAIL', $data->EMAIL)->update($updateData);
            
            if ($query) {
                return $this->respondUpdated($data, 200);
            } else {
                return $this->failServerError();
            }
        } else {
            return $this->failUnauthorized();
        }
        
    }
    
}