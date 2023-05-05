<?php

namespace App\Controllers;

class TransaksiAktivasi extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM transaksi_aktivasi');
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getJSON();

        $newId = $this->generateNewId();

        $insertData = [
            'ID_AKTIVASI' => $newId,
            'ID_MEMBER' => $data->ID_MEMBER,
            'ID_PEGAWAI' => $data->ID_PEGAWAI,
            'TANGGAL_AKTIVASI' =>  date("Y-m-d H:i:s"),
            'JUMLAH_PEMBAYARAN' => $data->JUMLAH_PEMBAYARAN,
            'MASA_BERLAKU_AKTIVASI' => date("Y-m-d H:i:s", strtotime("+1 year")),
        ];
        
        $query = $db->table('transaksi_aktivasi')->insert($insertData);
    
        if (!$query) 
            return $this->failServerError();
        
        //mengembalikan sisa pembayaran jika lebih dari 3 juta
        if($data->JUMLAH_PEMBAYARAN > 3000000){
           $sisa_transaksi = $data->JUMLAH_PEMBAYARAN - 3000000;
        } else {
            $sisa_transaksi = 0;
        }

        //mengambil sisa deposit uang member
        $member = $db->table('member')
        ->where('ID_MEMBER', $data->ID_MEMBER)
        ->get()
        ->getRow();

        //update member
        $updateMember = [
            'SISA_DEPOSIT_UANG' => $member->SISA_DEPOSIT_UANG + $sisa_transaksi,
            'STATUS' => "Aktif",
            'TANGGAL_KADALUARSA' => date("Y-m-d H:i:s", strtotime("+1 year")),
        ];
        
        $db->table('member')->where('ID_MEMBER', $data->ID_MEMBER)->update($updateMember);

        return $this->respondCreated($insertData);
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('transaksi_aktivasi')->delete(['ID_AKTIVASI' => $id]);
        if ($query) {
            return $this->respondDeleted(['id_aktivasi' => $id]);
        } else {
            return $this->failServerError();
        }
    }

    private function generateNewId()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_aktivasi) as max_id FROM transaksi_aktivasi');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? 'AK000', -3);
        $newNumber = $lastNumber + 1;

        return 'AK' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}