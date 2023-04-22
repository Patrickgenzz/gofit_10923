<?php

namespace App\Controllers;

class TransaksiDepositKelas extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM transaksi_deposit_kelas');
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
            'ID_KELAS' => 'required',
            'ID_PEGAWAI' => 'required',
            'JUMLAH_DEPOSIT_KELAS' => 'required',
            'JUMLAH_PEMBAYARAN' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }

        //membuat auto increment
        $maxId = $db->table('transaksi_deposit_kelas')
        ->selectMax('id_deposit_kelas')
        ->get()
        ->getRow()
        ->id_deposit_kelas;
        $newId = $maxId + 1;

        //mengecek apakah id promo ada diinputkan oleh user atau tidak
        // $idPromo = null;
        // if($data['ID_PROMO'] == null){
        //     $idPromo = "0";
        // }else{
        //     $idPromo = $data['ID_PROMO'];
        // }
        

        //mengambil bonus dari tabel promo berdasarkan id promo
        $cariBonus = $db->table('promo')
        ->where('ID_PROMO', $data['ID_PROMO'])
        ->get()
        ->getRow()
        ->BONUS;
        

        //mengambil minimal pembayaran dari tabel promo berdasarkan id promo
        $minimalPembelian = $db->table('promo')
        ->where('ID_PROMO', $data['ID_PROMO'])
        ->get()
        ->getRow()
        ->MINIMAL_PEMBELIAN;
        

        //menghitung bonus
        $bonus = null;
        if($data['JUMLAH_PEMBAYARAN'] < $minimalPembelian){
            $bonus = 0;
        }else{
            $bonus = $cariBonus;
        }       

        //mengecek apakah id promo ada berdasarkan tanggal sekarang tanpa inputan dari user
        // $cek = $db->table('promo')
        // ->where('ID_PROMO', $data['ID_PROMO'])
        // ->where('TANGGAL_AKHIR_PROMO >=', date("Y-m-d H:i:s"))
        // ->where('TANGGAL_AWAL_PROMO <=', date("Y-m-d H:i:s"))
        // ->get()
        // ->getRow();

        $insertData = [
            'ID_DEPOSIT_KELAS' => $newId,
            'ID_MEMBER' => $data['ID_MEMBER'],
            'ID_PROMO' =>$data['ID_PROMO'],
            'ID_KELAS' => $data['ID_KELAS'],
            'ID_PEGAWAI' => $data['ID_PEGAWAI'],
            'TANGGAL_DEPOSIT_KELAS' =>  date("Y-m-d H:i:s"),
            'JUMLAH_DEPOSIT_KELAS' => $data['JUMLAH_DEPOSIT_KELAS'],
            'BONUS_DEPOSIT_KELAS' => $bonus,
            'MASA_BERLAKU_DEPOSIT_KELAS' => date("Y-m-d H:i:s"),
            'JUMLAH_PEMBAYARAN' => $data['JUMLAH_PEMBAYARAN'],
            'TOTAL_DEPOSIT_KELAS' => $data['JUMLAH_PEMBAYARAN'] + $bonus,
        ];
        
        $query = $db->table('transaksi_deposit_kelas')->insert($insertData);
        
        //update tanggal kadaluarsa member
        // $member = [
        //     'TANGGAL_KADALUARSA' => date("Y-m-d H:i:s"),
        // ];
        // $db->table('member')->where('ID_MEMBER', $data['ID_MEMBER'])->update($member);

        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('transaksi_deposit_kelas')->delete(['ID_DEPOSIT_KELAS' => $id]);
        if ($query) {
            return $this->respondDeleted(['id' => $id]);
        } else {
            return $this->failServerError();
        }
    }
}