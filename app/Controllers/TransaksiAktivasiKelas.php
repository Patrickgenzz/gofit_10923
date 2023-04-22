<?php

namespace App\Controllers;

class TransaksiAktivasiKelas extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM transaksi_aktivasi_kelas');
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
            'ID_PEGAWAI' => 'required',
            'JUMLAH_PEMBAYARAN' => 'required',
            // 'MASA_BERLAKU_AKTIVASI' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }

        //membuat auto increment
        $maxId = $db->table('transaksi_aktivasi_kelas')
        ->selectMax('id_aktivasi')
        ->get()
        ->getRow()
        ->id_aktivasi;
        $newId = $maxId + 1;

        $insertData = [
            'ID_AKTIVASI' => $newId,
            'ID_MEMBER' => $data['ID_MEMBER'],
            'ID_PEGAWAI' => $data['ID_PEGAWAI'],
            'TANGGAL_AKTIVASI' =>  date("Y-m-d H:i:s"),
            'JUMLAH_PEMBAYARAN' => $data['JUMLAH_PEMBAYARAN'],
            'MASA_BERLAKU_AKTIVASI' => date("Y-m-d H:i:s"),
        ];
        
        $query = $db->table('transaksi_aktivasi_kelas')->insert($insertData);
        
        //update tanggal kadaluarsa member
        $member = [
            'TANGGAL_KADALUARSA' => date("Y-m-d H:i:s"),
        ];
        $db->table('member')->where('ID_MEMBER', $data['ID_MEMBER'])->update($member);

        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('transaksi_aktivasi_kelas')->delete(['ID_AKTIVASI' => $id]);
        if ($query) {
            return $this->respondDeleted(['id_aktivasi' => $id]);
        } else {
            return $this->failServerError();
        }
    }
}