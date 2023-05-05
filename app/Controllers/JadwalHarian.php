<?php

namespace App\Controllers;

class JadwalHarian extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT * FROM jadwal_harian');
        $result = $query->getResultArray();
       
        return $this->respond($result, 200);
    }

    public function postCreate()
    {
        $db = db_connect();
        $query = $db->query('SELECT * FROM jadwal_umum');
        $jadwalUmum = $query->getResultArray();

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
                    'STATUS_KELAS' => "Belum Terlaksana",
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
                    'STATUS_KELAS' => "Belum Terlaksana",
                ];

                $query = $db->table('jadwal_harian')->insert($insertData);
                if (!$query) {
                    return $this->failServerError();
                }
            }
        }
        return $this->respondCreated($jadwalUmum);
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

    // public function deleteDelete($id = null)
    // {
    //     $db = db_connect();
    //     $query = $db->table('jadwal_harian')->delete(['ID_JADWAL_UMUM' => $id]);
    //     if ($query) {
    //         return $this->respondDeleted(['ID_JADWAL_UMUM' => $id]);
    //     } else {
    //         return $this->failServerError();
    //     }
    // }
}