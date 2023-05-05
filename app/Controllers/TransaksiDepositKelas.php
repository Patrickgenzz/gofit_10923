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
        $data = $this->request->getJSON();

        //membuat auto increment
        $maxId = $db->table('transaksi_deposit_kelas')
        ->selectMax('id_deposit_kelas')
        ->get()
        ->getRow()
        ->id_deposit_kelas;
        $newId = $maxId + 1;

        //mengecek apakah id promo ada diinputkan oleh user atau tidak
        if($data['ID_PROMO'] != null){
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
            
            if($data['JUMLAH_PEMBAYARAN'] > $minimalPembelian){
                $bonus = $cariBonus;    
            }else{
                $bonus = 0;
            }
            $idPromo = $data['ID_PROMO'];

        }else{
            $idPromo = "0";
            $bonus = 0;
        }
        
        

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