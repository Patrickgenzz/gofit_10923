<?php

namespace App\Controllers;

class JadwalHarian extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();

        // update the status of the class
        //kalau ada edit data tinggal pake and status != Batalkan
        $db->query('UPDATE jadwal_harian SET STATUS_KELAS = "Selesai" WHERE TANGGAL_JADWAL_HARIAN < NOW() AND STATUS_KELAS != "Libur" AND STATUS_KELAS != "Izin"'); 
        $db->query('UPDATE jadwal_harian SET STATUS_KELAS = "Sedang Berlangsung" WHERE TANGGAL_JADWAL_HARIAN BETWEEN NOW() AND NOW() + INTERVAL 1 HOUR AND STATUS_KELAS != "Libur" AND STATUS_KELAS != "Izin"');

        $query = $db->query('SELECT jh.*, k.JENIS_KELAS, i.NAMA_INSTRUKTUR, ju.HARI_JADWAL_UMUM, ju.SESI_JADWAL_UMUM
                FROM jadwal_harian jh
                JOIN jadwal_umum ju ON jh.ID_JADWAL_UMUM = ju.ID_JADWAL_UMUM
                JOIN kelas k ON ju.ID_KELAS = k.ID_KELAS
                JOIN instruktur i ON jh.ID_INSTRUKTUR = i.ID_INSTRUKTUR
                WHERE jh.STATUS_KELAS != "Selesai" AND jh.tanggal_jadwal_harian >= CURDATE()');
                   
        $result = $query->getResultArray();
       
        return $this->respond($result, 200);
    }

    public function getGetJadwalHarian(){
        $db = db_connect();

        $query = $db->query('SELECT jh.*, k.JENIS_KELAS, i.NAMA_INSTRUKTUR, ju.HARI_JADWAL_UMUM, ju.SESI_JADWAL_UMUM, k.TARIF_KELAS
                FROM jadwal_harian jh
                JOIN jadwal_umum ju ON jh.ID_JADWAL_UMUM = ju.ID_JADWAL_UMUM
                JOIN kelas k ON ju.ID_KELAS = k.ID_KELAS
                JOIN instruktur i ON jh.ID_INSTRUKTUR = i.ID_INSTRUKTUR
                WHERE jh.STATUS_KELAS != "Selesai" AND jh.tanggal_jadwal_harian >= CURDATE()
                ORDER BY jh.TANGGAL_JADWAL_HARIAN ASC');

        $result = $query->getResultArray();

        $response = [
            'data' => $result
        ];
    
        return $this->respond($response, 200);
    }

    public function postCreate()
    {
        $db = db_connect();
        $query = $db->query('SELECT * FROM jadwal_umum');
        $jadwalUmum = $query->getResultArray();

        if(!$jadwalUmum){
            return $this->fail('Tidak Ada Jadwal Umum!', 400);
        }

        foreach ($jadwalUmum as $data) {
            // get the date for the current day of the week
            $indonesianDayName = $data['HARI_JADWAL_UMUM'];
            $englishDayName = $this->convertIndonesianDayNameToEnglish($indonesianDayName);

            $date = $this->getDateForDayOfWeek($englishDayName)[0];
           
            // get the session time
            if($data['SESI_JADWAL_UMUM'] == "1"){
                $sesi = "08:00:00";
            }else if($data['SESI_JADWAL_UMUM'] == "2"){
                $sesi = "9:30:00";
            }else if($data['SESI_JADWAL_UMUM'] == "3"){
                $sesi = "17:00:00";
            }else if($data['SESI_JADWAL_UMUM'] == "4"){
                $sesi = "18:30:00";
            }
            
            // update the date with the session time
            $date = $date . " " . $sesi;

            // check if there is already a record for this date and jadwal umum id
            $existingDataQuery = $db->table('jadwal_harian')->where([
                'TANGGAL_JADWAL_HARIAN' => $date,
            ])->get();

            if ($existingDataQuery->getRow()) {
                $random = rand(1,60);
                
                // membuat jadwal harian baru dengan menambahkan random detik agar tidak duplicate
                $insertData = [
                    'TANGGAL_JADWAL_HARIAN' => date('Y-m-d H:i:s', strtotime($date . ' + ' . $random . ' second')),
                    'ID_JADWAL_UMUM' => $data['ID_JADWAL_UMUM'],
                    'ID_INSTRUKTUR' => $data['ID_INSTRUKTUR'],
                    'STATUS_KELAS' => "Belum Dimulai",
                ];

                $query = $db->table('jadwal_harian')->insert($insertData);

                if (!$query) {
                    return $this->failServerError();
                }

            } else {
                // insert the new record
                $insertData = [
                    'TANGGAL_JADWAL_HARIAN' => $date,
                    'ID_JADWAL_UMUM' => $data['ID_JADWAL_UMUM'],
                    'ID_INSTRUKTUR' => $data['ID_INSTRUKTUR'],
                    'STATUS_KELAS' => "Belum Dimulai",
                ];

                $query = $db->table('jadwal_harian')->insert($insertData);
                
                if (!$query) {
                    return $this->failServerError();
                }
            }
        }

        $result = $db->query('SELECT jh.*, k.JENIS_KELAS, i.NAMA_INSTRUKTUR, ju.HARI_JADWAL_UMUM, ju.SESI_JADWAL_UMUM
                FROM jadwal_harian jh
                JOIN jadwal_umum ju ON jh.ID_JADWAL_UMUM = ju.ID_JADWAL_UMUM
                JOIN kelas k ON ju.ID_KELAS = k.ID_KELAS
                JOIN instruktur i ON jh.ID_INSTRUKTUR = i.ID_INSTRUKTUR
                WHERE jh.STATUS_KELAS != "Selesai" AND jh.tanggal_jadwal_harian >= CURDATE()')
        ->getResultArray();

        return $this->respondCreated($result, 'Jadwal Harian Berhasil Digenerate!');
    }

    public function putUpdate($id = null)
    {
        $db = db_connect();
        $data = $this->request->getJSON();

        $updateData = [
            'STATUS_KELAS' => $data->STATUS_KELAS,
        ];

        $query = $db->table('jadwal_harian')->update($updateData, ['TANGGAL_JADWAL_HARIAN' => $id]);

        if ($query) {
            return $this->respondUpdated($data, 200);
        } else {
            return $this->failServerError();
        }
    }
    
    private function getDateForDayOfWeek($dayOfWeek)
    {
        $currentDate = date('Y-m-d');
        $currentDayOfWeek = date('l', strtotime($currentDate));
        $daysToAdd = $this->getDaysToAdd($currentDayOfWeek, $dayOfWeek);
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($currentDate . ' + ' . ($daysToAdd + $i) . ' days'));
            $dates[] = $date;
        }

        return $dates;
    }

    private function convertIndonesianDayNameToEnglish($indonesianDayName)
    {
        $dayNames = [
            'Senin' => 'Monday',
            'Selasa' => 'Tuesday',
            'Rabu' => 'Wednesday',
            'Kamis' => 'Thursday',
            'Jumat' => 'Friday',
            'Sabtu' => 'Saturday',
            'Minggu' => 'Sunday',
        ];
        return $dayNames[$indonesianDayName];
    }

    private function getDaysToAdd($currentDayOfWeek, $dayOfWeek)
    {
        $daysOfWeek = [
            'Monday' => 0,
            'Tuesday' => 1,
            'Wednesday' => 2,
            'Thursday' => 3,
            'Friday' => 4,
            'Saturday' => 5,
            'Sunday' => 6,
        ];
        $currentDayOfWeekNumber = $daysOfWeek[$currentDayOfWeek];
        $dayOfWeekNumber = $daysOfWeek[$dayOfWeek];

        if ($dayOfWeekNumber >= $currentDayOfWeekNumber) {
            $daysToAdd = $dayOfWeekNumber - $currentDayOfWeekNumber;
        } else {
            $daysToAdd = 7 - $currentDayOfWeekNumber + $dayOfWeekNumber;
        }
        return $daysToAdd;
    }

    public function postDelete(){
        $db = db_connect();
        
        $query =  $db->table('jadwal_harian')->emptyTable();
        if (!$query) {
            return $this->failServerError();
        }
        return $this->respondDeleted('Jadwal Harian Berhasil Dihapus!');
    }

    public function getFind($cari = null)
    {
        $db = db_connect();
        
        $query =  $db->query('SELECT jh.*, k.JENIS_KELAS, i.NAMA_INSTRUKTUR, ju.HARI_JADWAL_UMUM, ju.SESI_JADWAL_UMUM 
                    FROM jadwal_harian jh 
                    JOIN jadwal_umum ju ON jh.ID_JADWAL_UMUM = ju.ID_JADWAL_UMUM 
                    JOIN kelas k ON ju.ID_KELAS = k.ID_KELAS 
                    JOIN instruktur i ON jh.ID_INSTRUKTUR = i.ID_INSTRUKTUR 
                    WHERE k.JENIS_KELAS LIKE "%' . $cari . '%"
                    OR i.NAMA_INSTRUKTUR LIKE "%' . $cari . '%"
                    OR ju.HARI_JADWAL_UMUM LIKE "%' . $cari . '%"
                    OR ju.SESI_JADWAL_UMUM LIKE "%' . $cari . '%"');

        $result = $query->getResultArray();            
                    
        if ($result) {
            return $this->respond($result, 200);
        } else {
            return $this->failNotFound();
        }
    }
}