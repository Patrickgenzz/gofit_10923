<?php

namespace App\Controllers;

class Member extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM member');
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function getMemberById($id = null){
        $db = db_connect();
        $query = $db->table('member')->where('ID_MEMBER', $id)->get();
        $result = $query->getRow(); 
        
        if ($result) {
            $depositKelas = $db->query('SELECT SUM(SISA_DEPOSIT_KELAS) AS SISA_DEPOSIT_KELAS
                    FROM deposit_kelas_member
                    WHERE ID_MEMBER = "'.$id.'"')->getRow();

            if(!$depositKelas->SISA_DEPOSIT_KELAS){
                $depositKelas->SISA_DEPOSIT_KELAS = 0;
            }

            $result->SISA_DEPOSIT_KELAS = $depositKelas->SISA_DEPOSIT_KELAS;

            return $this->respond($result, 200);
        } else {
            return $this->failNotFound('Member Tidak Ditemukan!');
        }
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getJSON();

        // Get the maximum id from the table and generate a new id
        $newId = $this->generateNewId();

        $insertData = [
            'ID_MEMBER' => $newId,
            'NAMA_MEMBER' => $data->NAMA_MEMBER,
            'ALAMAT_MEMBER' => $data->ALAMAT_MEMBER,
            'TANGGAL_LAHIR_MEMBER' =>$data->TANGGAL_LAHIR_MEMBER,
            'NO_TELEPON_MEMBER' => $data->NO_TELEPON_MEMBER,
            'SISA_DEPOSIT_UANG' => 0,
            'TANGGAL_KADALUARSA' => null,
            'STATUS' => "Tidak Aktif",
            'EMAIL' => $data->EMAIL,
            'PASSWORD' => password_hash($data->TANGGAL_LAHIR_MEMBER, PASSWORD_DEFAULT),
        ];

        $query = $db->table('member')->insert($insertData);
        
        if ($query) {
            return $this->respond($insertData, 200);;
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('member')->delete(['ID_MEMBER' => $id]);
        if ($query) {
            return $this->respondDeleted(['id' => $id]);
        } else {
            return $this->failServerError();
        }
    }

    public function putUpdate($id = null)
    {
        $db = db_connect();
        $data = $this->request->getJSON();

        $updateData = [
            'NAMA_MEMBER' => $data->NAMA_MEMBER,
            'ALAMAT_MEMBER' => $data->ALAMAT_MEMBER,
            'TANGGAL_LAHIR_MEMBER' => $data->TANGGAL_LAHIR_MEMBER,
            'NO_TELEPON_MEMBER' => $data->NO_TELEPON_MEMBER,
        ];

        $query = $db->table('member')->update($updateData, ['ID_MEMBER' => $id]);
        if ($query) {
            return $this->respondUpdated($data, 200);
        } else {
            return $this->failServerError();
        }
    }

    public function getFind($nama = null)
    {
        $db = db_connect();
        $query = $db->table('member')->like('NAMA_MEMBER', $nama)->get();

        $result = $query->getResultArray();

        if ($result) {
            return $this->respond($result, 200);
        } else {
            return $this->failNotFound();
        }
    }

    public function putResetPassword($id = null)
    {
        $db = db_connect();
        $data = $this->request->getJSON();

        $updateData = [
            'PASSWORD' => password_hash(date("Y-m-d"), PASSWORD_DEFAULT),
        ];

        $query = $db->table('member')->update($updateData, ['ID_MEMBER' => $id]);
        if ($query) {
            return $this->respondUpdated($data, 200);
        } else {
            return $this->failServerError();
        }
    }

    public function getTransaksiAktivasi($id = null){
        $db = db_connect();

        $query = $db->query('SELECT * FROM transaksi_aktivasi WHERE ID_MEMBER = "'.$id.'"');

        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
        return $this->respond($response, 200);
    }

    public function getTransaksiDepositUang($id = null){
        $db = db_connect();

        $query = $db->query('SELECT * FROM transaksi_deposit_uang WHERE ID_MEMBER = "'.$id.'"');

        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
        return $this->respond($response, 200);
    }

    public function getTransaksiDepositKelas($id = null){
        $db = db_connect();

        $query = $db->query('SELECT tdk.*, k.JENIS_KELAS FROM transaksi_deposit_kelas tdk
                JOIN kelas k on tdk.ID_KELAS = k.ID_KELAS 
                WHERE tdk.ID_MEMBER = "'.$id.'"');

        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
        return $this->respond($response, 200);
    }

    public function getListBookingKelas($id = null){
        $db = db_connect();

        $query = $db->query('SELECT pbk.*, k.JENIS_KELAS, k.ID_KELAS FROM presensi_booking_kelas pbk 
                JOIN jadwal_harian jh ON jh.TANGGAL_JADWAL_HARIAN = pbk.TANGGAL_DIBOOKING_KELAS 
                JOIN jadwal_umum ju ON jh.ID_JADWAL_UMUM = ju.ID_JADWAL_UMUM 
                JOIN kelas k ON ju.ID_KELAS = k.ID_KELAS 
                WHERE ID_MEMBER = "'.$id.'"');

        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
        return $this->respond($response, 200);
    }

    public function getListBookingGym($id = null){
        $db = db_connect();

        $query = $db->query('SELECT * FROM presensi_booking_gym WHERE ID_MEMBER = "'.$id.'"');

        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
        return $this->respond($response, 200);
    }

    private function generateNewId()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_member) as max_id FROM member');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? '000', -3);
        $newNumber = $lastNumber + 1;

        $year = date('y');
        $month = date('m');
        
        return $year.'.'.$month.'.' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}