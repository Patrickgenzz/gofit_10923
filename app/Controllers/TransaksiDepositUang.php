<?php

namespace App\Controllers;

class TransaksiDepositUang extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM transaksi_deposit_uang');
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
            'JUMLAH_DEPOSIT_UANG' => 'required',
        ]);

        if ($validation->run($data) === false) {
            return $this->failValidationErrors($validation->getErrors());
        }

        //membuat auto increment
        $maxId = $db->table('transaksi_deposit_uang')
        ->selectMax('id_deposit_uang')
        ->get()
        ->getRow()
        ->id_deposit_uang;
        $newId = $maxId + 1;

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

        //mengambil sisa deposit uang member
        $sisaDepositUang = $db->table('member')
        ->where('ID_MEMBER', $data['ID_MEMBER'])
        ->get()
        ->getRow()
        ->SISA_DEPOSIT_UANG;

         //menghitung bonus
         $bonus = null;
         if($data['JUMLAH_DEPOSIT_UANG'] < $minimalPembelian){
             $bonus = 0;
         }else{
             $bonus = $cariBonus;
         }       

        $insertData = [
            'ID_DEPOSIT_UANG' => $newId,
            'ID_MEMBER' => $data['ID_MEMBER'],
            'ID_PEGAWAI' => $data['ID_PEGAWAI'],
            'ID_PROMO' => $data['ID_PROMO'],
            'TANGGAL_DEPOSIT_UANG' =>  date("Y-m-d H:i:s"),
            'JUMLAH_DEPOSIT_UANG' => $data['JUMLAH_DEPOSIT_UANG'],
            'BONUS_DEPOSIT_UANG' => $bonus,
            'TOTAL_DEPOSIT_UANG' => $data['JUMLAH_DEPOSIT_UANG'] + $bonus,
        ];
        
        $query = $db->table('transaksi_deposit_uang')->insert($insertData);
        
        //update sisa deposit uang member
        $member = [
            'SISA_DEPOSIT_UANG' => $sisaDepositUang + $data['JUMLAH_DEPOSIT_UANG'] + $bonus,
            'STATUS' => 'Aktif',
        ];
        $db->table('member')->where('ID_MEMBER', $data['ID_MEMBER'])->update($member);

        if ($query) {
            return $this->respondCreated($insertData);
        }
        return $this->failServerError();
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('transaksi_deposit_uang')->delete(['ID_DEPOSIT_UANG' => $id]);
        if ($query) {
            return $this->respondDeleted(['id' => $id]);
        } else {
            return $this->failServerError();
        }
    }
}