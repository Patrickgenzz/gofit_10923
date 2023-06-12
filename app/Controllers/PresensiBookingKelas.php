<?php

namespace App\Controllers;

class PresensiBookingKelas extends BaseController
{
    public function getBookingKelasPaket()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT pbk.*, k.JENIS_KELAS, m.ID_MEMBER, m.NAMA_MEMBER, i.NAMA_INSTRUKTUR,  dkm.SISA_DEPOSIT_KELAS, tdk.MASA_BERLAKU_DEPOSIT_KELAS
            FROM jadwal_harian jh 
            JOIN instruktur i ON jh.id_instruktur = i.id_instruktur 
            JOIN jadwal_umum ju ON jh.id_jadwal_umum = ju.id_jadwal_umum 
            JOIN kelas k ON ju.id_kelas = k.id_kelas 
            JOIN presensi_booking_kelas pbk ON pbk.tanggal_dibooking_kelas = jh.tanggal_jadwal_harian 
            JOIN member m ON pbk.id_member = m.id_member 
            JOIN deposit_kelas_member dkm on dkm.ID_MEMBER = m.ID_MEMBER 
            AND dkm.ID_KELAS = k.ID_KELAS 
            JOIN transaksi_deposit_kelas tdk on tdk.ID_MEMBER = m.ID_MEMBER
            AND tdk.ID_KELAS = k.ID_KELAS
            WHERE pbk.TARIF_KELAS = 1');//AND pbk.STATUS = "Hadir" 
           
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function getBookingKelasReguler()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT pbk.*, k.JENIS_KELAS, m.NAMA_MEMBER, i.NAMA_INSTRUKTUR, m.SISA_DEPOSIT_UANG, m.ID_MEMBER
                    FROM jadwal_harian jh 
                    JOIN instruktur i ON jh.id_instruktur = i.id_instruktur 
                    JOIN jadwal_umum ju ON jh.id_jadwal_umum = ju.id_jadwal_umum 
                    JOIN kelas k ON ju.id_kelas = k.id_kelas 
                    JOIN presensi_booking_kelas pbk ON pbk.tanggal_dibooking_kelas = jh.tanggal_jadwal_harian 
                    JOIN member m ON pbk.id_member = m.id_member 
                    WHERE pbk.TARIF_KELAS > 1');
                    //AND pbk.STATUS = "Hadir" 
           
        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getJSON();

        $newId = $this->generateNewId();

        $presensi = $db->table('presensi_booking_kelas')->where('ID_MEMBER', $data->ID_MEMBER)->where('TANGGAL_DIBOOKING_KELAS', $data->TANGGAL_DIBOOKING_KELAS)->get()->getRow();

        if($presensi){
            return $this->response->setStatusCode(406);
        }

        $member = $db->table('member')->where('ID_MEMBER', $data->ID_MEMBER)->get()->getRow();

        if($member->STATUS != "Aktif"){
            return $this->failNotFound('Member Sudah Tidak Aktif!');
        }

        $kelas = $db->query('SELECT k.* FROM jadwal_harian jh
                    JOIN jadwal_umum ju ON jh.id_jadwal_umum = ju.id_jadwal_umum
                    JOIN kelas k ON ju.id_kelas = k.id_kelas
                    WHERE jh.tanggal_jadwal_harian = "'.$data->TANGGAL_DIBOOKING_KELAS.'"')->getRow();

        if($kelas->KUOTA < 1){
            return $this->response->setStatusCode(403);
        }

        $depositKelas = $db->table('deposit_kelas_member')->where('ID_MEMBER', $member->ID_MEMBER)->where('ID_KELAS', $kelas->ID_KELAS)->get()->getRow();
        
        if(!$depositKelas || $depositKelas->SISA_DEPOSIT_KELAS < 1){
            if($member->SISA_DEPOSIT_UANG < $kelas->TARIF_KELAS){
                return $this->response->setStatusCode(402);
            }else{
                // $updateMember = [
                //     'SISA_DEPOSIT_UANG' => $member->SISA_DEPOSIT_UANG - $kelas->TARIF_KELAS,
                // ];
                        
                // $query = $db->table('member')->update($updateMember, ['ID_MEMBER' =>  $member->ID_MEMBER]);

                $biaya = $kelas->TARIF_KELAS;
            }
        }else{
            // $updateDeposit = [
            //     'SISA_DEPOSIT_KELAS' => $depositKelas->SISA_DEPOSIT_KELAS - 1,
            // ];
            
            // $query = $db->table('deposit_kelas_member')->update($updateDeposit, ['ID_DEPOSIT_KELAS_MEMBER' => $depositKelas->ID_DEPOSIT_KELAS_MEMBER]);
            
            $biaya = 1;
        }

        $updateKelas = [
            'KUOTA' => $kelas->KUOTA - 1,
        ];

        $query = $db->table('kelas')->update($updateKelas, ['ID_KELAS' => $kelas->ID_KELAS]);
        
        $insertData = [
            'ID_BOOKING_KELAS' => $newId,
            'ID_MEMBER' => $data->ID_MEMBER,
            'TANGGAL_BOOKING_KELAS' => date("Y-m-d H:i:s"),
            'TANGGAL_DIBOOKING_KELAS' => $data->TANGGAL_DIBOOKING_KELAS,
            'WAKTU_PRESENSI_KELAS' => null,
            'TARIF_KELAS' => $biaya,
            'STATUS' => "Booking"
        ];
        
        $query = $db->table('presensi_booking_kelas')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($insertData);
        } else {
            return $this->failServerError();
        }
    }

    public function getBookingKelasById($id = null){
        $db = db_connect();

        $db->query('UPDATE presensi_booking_kelas AS pbk
                JOIN jadwal_harian jh ON pbk.TANGGAL_DIBOOKING_KELAS = jh.TANGGAL_JADWAL_HARIAN
                JOIN jadwal_umum ju ON jh.id_jadwal_umum = ju.id_jadwal_umum
                JOIN kelas k ON ju.id_kelas = k.id_kelas
                JOIN member AS m ON pbk.ID_MEMBER = m.ID_MEMBER
                JOIN deposit_kelas_member AS dkm ON m.ID_MEMBER = dkm.ID_MEMBER
                
                SET pbk.STATUS = "Tidak Hadir",
                    m.SISA_DEPOSIT_UANG = CASE
                        WHEN pbk.TARIF_KELAS > 1 AND m.SISA_DEPOSIT_UANG >= pbk.TARIF_KELAS THEN m.SISA_DEPOSIT_UANG - pbk.TARIF_KELAS
                        ELSE m.SISA_DEPOSIT_UANG
                    END,
                    dkm.SISA_DEPOSIT_KELAS = CASE
                        WHEN pbk.TARIF_KELAS = 1 AND dkm.SISA_DEPOSIT_KELAS >= 1 AND dkm.ID_KELAS = k.ID_KELAS THEN dkm.SISA_DEPOSIT_KELAS - 1
                        ELSE dkm.SISA_DEPOSIT_KELAS
                    END
                
                WHERE TIMESTAMP(pbk.TANGGAL_DIBOOKING_KELAS) + INTERVAL 2 HOUR <= NOW()
                    AND pbk.STATUS != "Batal"
                    AND pbk.STATUS != "Hadir"
                    AND pbk.STATUS != "Tidak Hadir"'); 
    
        $query = $db->query('SELECT pbk.*, ju.HARI_JADWAL_UMUM, ju.SESI_JADWAL_UMUM, k.JENIS_KELAS, m.NAMA_MEMBER
                FROM jadwal_harian jh 
                JOIN instruktur i ON jh.id_instruktur = i.id_instruktur
                JOIN jadwal_umum ju ON jh.id_jadwal_umum = ju.id_jadwal_umum 
                JOIN kelas k ON ju.id_kelas = k.id_kelas
                JOIN presensi_booking_kelas pbk ON pbk.tanggal_dibooking_kelas = jh.tanggal_jadwal_harian
                JOIN member m ON pbk.id_member = m.id_member
                WHERE pbk.status != "Hadir"
                AND pbk.status != "Batal"
                AND pbk.status != "Tidak Hadir"
                AND i.id_instruktur = "'.$id.'"
                ORDER BY jh.TANGGAL_JADWAL_HARIAN ASC');

        $result = $query->getResultArray();
        
        $response = [
            'data' => $result
        ];
    
        return $this->respond($response, 200);
    }

    public function putUpdate($id = null){
        $db = db_connect();

        $bookingKelas = $db->query('SELECT * from presensi_booking_kelas where id_booking_kelas = "'.$id.'"')->getRow();
        $member = $db->table('member')->where('ID_MEMBER', $bookingKelas->ID_MEMBER)->get()->getRow();

        $kelas = $db->query('SELECT k.* FROM jadwal_harian jh
                    JOIN jadwal_umum ju ON jh.id_jadwal_umum = ju.id_jadwal_umum
                    JOIN kelas k ON ju.id_kelas = k.id_kelas
                    WHERE jh.tanggal_jadwal_harian = "'.$bookingKelas->TANGGAL_DIBOOKING_KELAS.'"')->getRow();

        $depositKelas = $db->table('deposit_kelas_member')->where('ID_KELAS', $kelas->ID_KELAS)->where('ID_MEMBER', $member->ID_MEMBER)->get()->getRow();

        if($bookingKelas->STATUS == "Hadir"){
            return $this->response->setStatusCode(406);
        }

        if($bookingKelas->TARIF_KELAS > 1){
            if($member->SISA_DEPOSIT_UANG < $bookingKelas->TARIF_KELAS){
                return $this->response->setStatusCode(402);
            }else{
                $updateMember = [
                    'SISA_DEPOSIT_UANG' => $member->SISA_DEPOSIT_UANG - $bookingKelas->TARIF_KELAS,
                ];
                        
                $query = $db->table('member')->update($updateMember, ['ID_MEMBER' =>  $member->ID_MEMBER]);
            }
        }else{
            if($depositKelas->SISA_DEPOSIT_KELAS < 1){
                return $this->response->setStatusCode(402);
            }else{
                $updateDeposit = [
                    'SISA_DEPOSIT_KELAS' => $depositKelas->SISA_DEPOSIT_KELAS - 1,
                ];
                        
                $query = $db->table('deposit_kelas_member')->update($updateDeposit, ['ID_DEPOSIT_KELAS_MEMBER' => $depositKelas->ID_DEPOSIT_KELAS_MEMBER]);
            }
        }

        $updateKelas = [
            'STATUS' => "Hadir",
            'WAKTU_PRESENSI_KELAS' => date("Y-m-d H:i:s"),
        ];

        $query = $db->table('presensi_booking_kelas')->update($updateKelas, ['ID_BOOKING_KELAS' => $id]);

        if (!$query) {
            return $this->failServerError('Failed To Update Booking Status', 500);
        } else {
            $response = [
                'status' => 200,
                'message' => 'Booking Status Updated Successfully'
            ];
            return $this->respond($response, 200);
        }
    }

    public function putBatalBookingKelas($idBooking = null, $idKelas = null){
        $db = db_connect();
        
        $booking = $db->table('presensi_booking_kelas')->where('ID_BOOKING_KELAS', $idBooking)->get()->getRow();
    
        $tanggalBooking = strtotime($booking->TANGGAL_DIBOOKING_KELAS);
        $tanggalSekarang = strtotime(date("Y-m-d H:i:s"));
    
        $diff = $tanggalBooking - $tanggalSekarang;
    
        $jam = floor($diff / (60 * 60));
    
        if ($jam < 24) {
            return $this->response->setStatusCode(404);
        }
    
        $updateKelas = $db->query('UPDATE kelas SET KUOTA = KUOTA + 1 WHERE ID_KELAS = "'.$idKelas.'"');
       
        $query = $db->table('presensi_booking_kelas')->where('ID_BOOKING_KELAS', $idBooking)->update(['STATUS' => "Batal"]);

        if (!$query) {
            return $this->failServerError('Failed to update booking status', 500);
        } else {
            $response = [
                'status' => 200,
                'message' => 'Booking status updated successfully'
            ];
            return $this->respond($response, 200);
        }

    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('presensi_booking_kelas')->delete(['ID_BOOKING_KELAS' => $id]);
        if ($query) {
            return $this->respondDeleted(['id_booking_kelas' => $id]);
        } else {
            return $this->failServerError();
        }
    }

    private function generateNewId()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_booking_kelas) as max_id FROM presensi_booking_kelas');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? '000', -3);
        $newNumber = $lastNumber + 1;

        $year = date('y');
        $month = date('m');
        
        return $year.'.'.$month.'.' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}