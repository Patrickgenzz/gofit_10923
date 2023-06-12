<?php

namespace App\Controllers;

class TransaksiDepositUang extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT du.*, m.NAMA_MEMBER, p.NAMA_PEGAWAI 
                        FROM transaksi_deposit_uang du
                        JOIN member m ON du.ID_MEMBER = m.ID_MEMBER
                        JOIN pegawai p ON du.ID_PEGAWAI = p.ID_PEGAWAI');
                        
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getJSON();

        $newId = $this->generateNewId();

        //jika jumlah deposit uang kurang dari 500000 return error
        if($data->JUMLAH_DEPOSIT_UANG < 500000){
            return $this->fail('Minimal Deposit Uang Adalah 500000!', 400);
        }

        // mengambil bonus dan minimal pembayaran dari tabel promo berdasarkan tanggal hari ini dengan tanggal mulai dan tanggal selesai
        $promo = $db->query("SELECT * FROM promo WHERE WAKTU_MULAI_PROMO <= CURDATE() 
                    AND WAKTU_SELESAI_PROMO >= CURDATE() 
                    AND JENIS_PROMO = 'Uang' 
                    AND MINIMAL_PEMBELIAN < $data->JUMLAH_DEPOSIT_UANG")->getRow();

        //jika tidak ada promo yang aktif maka promo = promo dengan id PO000
        if(!$promo){
            $promo = $db->query("SELECT * FROM promo WHERE ID_PROMO = 'PO000'")->getRow();
        }

        //bonus masih salah

        //bonus deposit uang
        $bonus = $promo->BONUS;

        //mengambil sisa deposit uang member
        $member = $db->table('member')
        ->where('ID_MEMBER', $data->ID_MEMBER)
        ->get()
        ->getRow();

        $insertData = [
            'ID_DEPOSIT_UANG' => $newId,
            'ID_MEMBER' => $data->ID_MEMBER,
            'ID_PEGAWAI' => $data->ID_PEGAWAI,
            'ID_PROMO' => $promo->ID_PROMO,
            'TANGGAL_DEPOSIT_UANG' =>  date("Y-m-d H:i:s"),
            'JUMLAH_DEPOSIT_UANG' => $data->JUMLAH_DEPOSIT_UANG,
            'BONUS_DEPOSIT_UANG' => $bonus,
            'TOTAL_DEPOSIT_UANG' => $member->SISA_DEPOSIT_UANG + $data->JUMLAH_DEPOSIT_UANG + $bonus,
        ];
        
        $query = $db->table('transaksi_deposit_uang')->insert($insertData);
        
        if (!$query) {
            return $this->failServerError();
        }

        $pegawai = $db->table('pegawai')
        ->where('ID_PEGAWAI', $data->ID_PEGAWAI)
        ->get()
        ->getRow();

        $updateMember = [
            'SISA_DEPOSIT_UANG' => $member->SISA_DEPOSIT_UANG + $data->JUMLAH_DEPOSIT_UANG + $bonus,
        ];
        
        $db->table('member')->where('ID_MEMBER', $data->ID_MEMBER)->update($updateMember);

        $insertData['NAMA_MEMBER'] = $member->NAMA_MEMBER;
        $insertData['NAMA_PEGAWAI'] = $pegawai->NAMA_PEGAWAI;

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
        $lastNumber = (int) substr($maxIdResult->max_id ?? '000', -3);
        $newNumber = $lastNumber + 1;

        $year = date('y');
        $month = date('m');
        
        return $year.'.'.$month.'.' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}