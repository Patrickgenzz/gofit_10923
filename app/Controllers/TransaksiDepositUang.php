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
        $data = $this->request->getJSON();

        $newId = $this->generateNewId();

        // mengambil bonus dan minimal pembayaran dari tabel promo berdasarkan id promo
        $promo = $db->table('promo')
        ->where('ID_PROMO', $data->ID_PROMO)
        ->get()
        ->getRow();

        //jika jumlah deposit uang kurang dari 500000 return error
        if($data->JUMLAH_DEPOSIT_UANG < 500000){
            return $this->fail('Minimal Deposit Uang Adalah 500000!', 400);
        }

        //mengecek promo
        if($data->JUMLAH_DEPOSIT_UANG < $promo->MINIMAL_PEMBELIAN){
             $bonus = 0;
        }else{
             $bonus = $promo->BONUS;
        }       

        $insertData = [
            'ID_DEPOSIT_UANG' => $newId,
            'ID_MEMBER' => $data->ID_MEMBER,
            'ID_PEGAWAI' => $data->ID_PEGAWAI,
            'ID_PROMO' => $data->ID_PROMO,
            'TANGGAL_DEPOSIT_UANG' =>  date("Y-m-d H:i:s"),
            'JUMLAH_DEPOSIT_UANG' => $data->JUMLAH_DEPOSIT_UANG,
            'BONUS_DEPOSIT_UANG' => $bonus,
            'TOTAL_DEPOSIT_UANG' => $data->JUMLAH_DEPOSIT_UANG + $bonus,
        ];
        
        $query = $db->table('transaksi_deposit_uang')->insert($insertData);
        
        if (!$query) {
            return $this->failServerError();
        }

        //mengambil sisa deposit uang member
        $member = $db->table('member')
        ->where('ID_MEMBER', $data->ID_MEMBER)
        ->get()
        ->getRow();

        $updateMember = [
            'SISA_DEPOSIT_UANG' => $member->SISA_DEPOSIT_UANG + $data->JUMLAH_DEPOSIT_UANG + $bonus,
        ];
        
        $db->table('member')->where('ID_MEMBER', $data->ID_MEMBER)->update($updateMember);

        return $this->respondCreated($insertData);
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

    private function generateNewId()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_deposit_uang) as max_id FROM transaksi_deposit_uang');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? 'DU000', -3);
        $newNumber = $lastNumber + 1;

        return 'DU' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}