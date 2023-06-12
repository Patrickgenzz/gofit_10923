<?php

namespace App\Controllers;

class TransaksiDepositKelas extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT dk.*, m.NAMA_MEMBER, p.NAMA_PEGAWAI, k.JENIS_KELAS, pr.JENIS_PROMO
                        FROM transaksi_deposit_kelas dk
                        JOIN member m on dk.ID_MEMBER = m.ID_MEMBER
                        JOIN pegawai p on dk.ID_PEGAWAI = p.ID_PEGAWAI
                        JOIN kelas k on dk.ID_KELAS = k.ID_KELAS
                        JOIN promo pr on dk.ID_PROMO = pr.ID_PROMO');
                        
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getJSON();

        $newId = $this->generateNewId();
        $newIdKelasMember = $this->generateNewIdKelasMember();

        // Mengambil bonus dan minimal pembayaran dari tabel promo berdasarkan tanggal hari ini dengan tanggal mulai dan tanggal selesai
        $promo = $db->query("SELECT * FROM promo 
            WHERE WAKTU_MULAI_PROMO <= CURDATE() 
            AND WAKTU_SELESAI_PROMO >= CURDATE() 
            AND JENIS_PROMO = 'Kelas' 
            AND MINIMAL_PEMBELIAN <= $data->JUMLAH_DEPOSIT_KELAS 
            ORDER BY BONUS DESC 
            LIMIT 1")
        ->getRow();

        // Jika tidak ada promo yang aktif maka promo = promo dengan id PO000
        if (!$promo) {
            $promo = $db->query("SELECT * FROM promo WHERE ID_PROMO = 'PO000'")->getRow();
        }

        // Bonus deposit kelas
        $bonus = $promo->BONUS;

        // insert atau update tabel deposit_kelas_member
        $kelasMember = $db->query("SELECT * FROM deposit_kelas_member WHERE ID_MEMBER = '$data->ID_MEMBER' AND ID_KELAS = '$data->ID_KELAS'")->getRow();

        if(!$kelasMember){
            $insertKelasMember = [
                'ID_DEPOSIT_KELAS_MEMBER' => $newIdKelasMember,
                'ID_MEMBER' => $data->ID_MEMBER,
                'ID_KELAS' => $data->ID_KELAS,
                'SISA_DEPOSIT_KELAS' => $data->JUMLAH_DEPOSIT_KELAS + $bonus,
            ];
    
            $kelasMember = $db->table('deposit_kelas_member')->insert($insertKelasMember);
        }else{
            $updateKelasMember = [
                'SISA_DEPOSIT_KELAS' => $kelasMember->SISA_DEPOSIT_KELAS + $data->JUMLAH_DEPOSIT_KELAS + $bonus,
            ];
    
            $kelasMember = $db->table('deposit_kelas_member')->update($updateKelasMember, ['ID_DEPOSIT_KELAS_MEMBER' => $kelasMember->ID_DEPOSIT_KELAS_MEMBER]);
        }

        // insert tabel transaksi_deposit_kelas
        $insertData = [
            'ID_DEPOSIT_KELAS' => $newId,
            'ID_MEMBER' => $data->ID_MEMBER,
            'ID_PROMO' => $promo->ID_PROMO,
            'ID_KELAS' => $data->ID_KELAS,
            'ID_PEGAWAI' => $data->ID_PEGAWAI,
            'TANGGAL_DEPOSIT_KELAS' =>  date("Y-m-d H:i:s"),
            'JUMLAH_DEPOSIT_KELAS' => $data->JUMLAH_DEPOSIT_KELAS,
            'BONUS_DEPOSIT_KELAS' => $bonus,
            'MASA_BERLAKU_DEPOSIT_KELAS' => date("Y-m-d H:i:s", strtotime("+1 month")),
            'JUMLAH_PEMBAYARAN' => $data->JUMLAH_PEMBAYARAN,
            'TOTAL_DEPOSIT_KELAS' => $data->JUMLAH_DEPOSIT_KELAS + $bonus,
        ];
        
        $query = $db->table('transaksi_deposit_kelas')->insert($insertData);

        $member = $db->query("SELECT * FROM member WHERE ID_MEMBER = '$data->ID_MEMBER'")->getRow();
        $pegawai = $db->query("SELECT * FROM pegawai WHERE ID_PEGAWAI = '$data->ID_PEGAWAI'")->getRow();
        $promo = $db->query("SELECT * FROM promo WHERE ID_PROMO = '$promo->ID_PROMO'")->getRow();
        $kelas = $db->query("SELECT * FROM kelas WHERE ID_KELAS = '$data->ID_KELAS'")->getRow();

        // Menambahkan data member, pegawai, promo, dan kelas ke dalam array $insertData
        $insertData['NAMA_MEMBER'] = $member->NAMA_MEMBER;
        $insertData['NAMA_PEGAWAI'] = $pegawai->NAMA_PEGAWAI;
        $insertData['JENIS_PROMO'] = $promo->JENIS_PROMO;
        $insertData['JENIS_KELAS'] = $kelas->JENIS_KELAS;
        
        if ($query) {
            return $this->respondCreated($insertData);
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

    private function generateNewId()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_deposit_kelas) as max_id FROM transaksi_deposit_kelas');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? '000', -3);
        $newNumber = $lastNumber + 1;

        $year = date('y');
        $month = date('m');
        
        return $year.'.'.$month.'.' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    private function generateNewIdKelasMember()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_deposit_kelas_member) as max_id FROM deposit_kelas_member');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? 'DKM000', -3);
        $newNumber = $lastNumber + 1;

        return 'DKM' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}