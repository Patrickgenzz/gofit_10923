<?php

namespace App\Controllers;

class PresensiBookingGym extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT pbg.*, m.NAMA_MEMBER 
            FROM presensi_booking_gym pbg 
            JOIN member m ON pbg.id_member = m.id_member
            ORDER BY pbg.TANGGAL_DIBOOKING_GYM ASC');

        $result = $query->getResultArray();
        
        return $this->respond($result, 200);
    }

    public function getGetBookingGym($id = null){
        $db = db_connect();

        $query = $db->query('SELECT * FROM presensi_booking_gym 
                        WHERE ID_MEMBER = "'.$id.'" 
                        AND STATUS != "Batal" 
                        AND TANGGAL_DIBOOKING_GYM >= CURDATE()
                        ORDER BY TANGGAL_DIBOOKING_GYM ASC');

        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
        return $this->respond($response, 200);
    }

    public function postCreate()
    {   
        $db = db_connect();
        $data = $this->request->getJSON();

        $newId = $this->generateNewId();

        $member = $db->table('member')->where('ID_MEMBER', $data->ID_MEMBER)->get()->getRow();

        if($member->STATUS != "Aktif"){
            return $this->failNotFound('Member Sudah Tidak Aktif!');
        }

        $sehari = $db->query('SELECT * FROM presensi_booking_gym 
                        WHERE ID_MEMBER = "'.$data->ID_MEMBER.'" 
                        AND DATE(TANGGAL_BOOKING_GYM) = CURDATE()');

        $result = $sehari->getResultArray();

        //cuman bisa sekali booking per hari
        if(count($result) > 0){
            return $this->response->setStatusCode(406); 
        }

        $tanggal = $db->query('SELECT * FROM presensi_booking_gym 
                WHERE ID_MEMBER = "'.$data->ID_MEMBER.'"
                AND STATUS != "Batal" 
                AND DATE_FORMAT(TANGGAL_DIBOOKING_GYM, "%Y-%m-%d") = DATE_FORMAT("'.$data->TANGGAL_DIBOOKING_GYM.'", "%Y-%m-%d")');

        $result = $tanggal->getResultArray();

        //tidak bisa booking di tanggal yang sama
        if(count($result) > 0){
            return $this->response->setStatusCode(403);
        }

        $limit = $db->query('SELECT * FROM presensi_booking_gym 
            WHERE DATE_FORMAT(TANGGAL_DIBOOKING_GYM, "%Y-%m-%d") = DATE_FORMAT("'.$data->TANGGAL_DIBOOKING_GYM.'", "%Y-%m-%d")
            GROUP BY DATE_FORMAT(TANGGAL_DIBOOKING_GYM, "%Y-%m-%d")
            HAVING COUNT(*) >= 10');

        $result = $limit->getResultArray();

        if(count($result) > 0){
            return $this->response->setStatusCode(402);
        }

        if($data->TANGGAL_DIBOOKING_GYM < date("Y-m-d H:i:s")){
            return $this->response->setStatusCode(405);
        }

        $insertData = [
            'ID_BOOKING_GYM' => $newId,
            'ID_MEMBER' => $data->ID_MEMBER,
            'TANGGAL_BOOKING_GYM' =>  date("Y-m-d H:i:s"),
            'TANGGAL_DIBOOKING_GYM' => $data->TANGGAL_DIBOOKING_GYM,
            'WAKTU_PRESENSI_GYM' => null,
            'SLOT_WAKTU_GYM' => 2,
            'STATUS' => "Booking"
        ];
        
        $query = $db->table('presensi_booking_gym')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($insertData);
        } else {
            return $this->failServerError();
        }
    }

    public function putUpdate($id = null){
        $db = db_connect();
        
        $updateData = [
            'STATUS' => "Hadir",
            'WAKTU_PRESENSI_GYM' => date("Y-m-d H:i:s"),
        ];

        $query = $db->table('presensi_booking_gym')->update($updateData, ['ID_BOOKING_GYM' => $id]);

        if($query){
            $bookingGym = $db->table('presensi_booking_gym')->where('ID_BOOKING_GYM', $id)->get()->getRow();

            $member = $db->table('member')->where('ID_MEMBER', $bookingGym->ID_MEMBER)->get()->getRow();
            $bookingGym->NAMA_MEMBER = $member->NAMA_MEMBER;
    
            return $this->respondUpdated($bookingGym);
        }else{
            return $this->failServerError();
        }
    }

    public function putBatalBookingGym($id = null){
        $db = db_connect();
        
        $booking = $db->table('presensi_booking_gym')->where('ID_BOOKING_GYM', $id)->get()->getRow();
    
        $tanggalBooking = strtotime($booking->TANGGAL_DIBOOKING_GYM);
        $tanggalSekarang = strtotime(date("Y-m-d H:i:s"));
    
        $diff = $tanggalBooking - $tanggalSekarang;
    
        $jam = floor($diff / (60 * 60));
    
        if ($jam < 24) {
            return $this->response->setStatusCode(404);
        }
       
        $query = $db->table('presensi_booking_gym')->where('ID_BOOKING_GYM', $id)->update(['STATUS' => "Batal"]);

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
        $query = $db->table('presensi_booking_gym')->delete(['ID_BOOKING_GYM' => $id]);
        if ($query) {
            return $this->respondDeleted(['id' => $id]);
        } else {
            return $this->failServerError();
        }
    }

    private function generateNewId()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_booking_gym) as max_id FROM presensi_booking_gym');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? '000', -3);
        $newNumber = $lastNumber + 1;

        $year = date('y');
        $month = date('m');
        
        return $year.'.'.$month.'.' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}