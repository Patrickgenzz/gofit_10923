<?php

namespace App\Controllers;

class IzinInstruktur extends BaseController
{
    public function getIndex()
    {
        $db = db_connect();
    
        $query = $db->query('SELECT ii.*, i.NAMA_INSTRUKTUR 
                        FROM izin_instruktur ii
                        JOIN instruktur i 
                        ON ii.ID_INSTRUKTUR = i.ID_INSTRUKTUR');
                        
        $result = $query->getResultArray();
       
        return $this->respond($result, 200);
    }

    public function getIzinInstrukturById($id = null){
        $db = db_connect();
        
        $query = $db->query('SELECT * FROM izin_instruktur
                            WHERE ID_INSTRUKTUR = "'.$id.'"');
                            
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

        if($data->INSTRUKTUR_PENGGANTI == "Instruktur Pengganti"){
            $data->INSTRUKTUR_PENGGANTI = "Tidak Ada";
        }

        $tanggalBooking = strtotime($data->TANGGAL_IZIN);
        $tanggalSekarang = strtotime(date("Y-m-d H:i:s"));
    
        $diff = $tanggalBooking - $tanggalSekarang;
    
        $jam = floor($diff / (60 * 60));
    
        if ($jam < 24) {
            return $this->response->setStatusCode(404);
        }

        $insertData = [
            'ID_IZIN_INSTRUKTUR' => $newId,
            'ID_INSTRUKTUR' => $data->ID_INSTRUKTUR,
            'TANGGAL_PEMBUATAN' => date("Y-m-d H:i:s"),
            'TANGGAL_IZIN' => $data->TANGGAL_IZIN,
            'ALASAN_IZIN' => $data->ALASAN_IZIN,
            'STATUS_KONFIRMASI_IZIN' => "Menunggu Konfirmasi",
            'TANGGAL_KONFIRMASI' => null,
            'INSTRUKTUR_PENGGANTI' => $data->INSTRUKTUR_PENGGANTI,
        ];
        
        $query = $db->table('izin_instruktur')->insert($insertData);
        
        if ($query) {
            return $this->respondCreated($data);
        } else {
            return $this->failServerError();
        }
    }

    public function putUpdate($id = null, $tanggal = null){
        $db = db_connect();
        
        $updateData = [
            'STATUS_KONFIRMASI_IZIN' => "Dikonfirmasi",
            'TANGGAL_KONFIRMASI' => date("Y-m-d H:i:s"),
        ];

        $query = $db->table('izin_instruktur')->update($updateData, ['ID_IZIN_INSTRUKTUR' => $id]);

        $izin = $db->table('izin_instruktur')->where('ID_IZIN_INSTRUKTUR', $id)->get()->getRow();

        if($izin->INSTRUKTUR_PENGGANTI == "Tidak Ada"){
            $updateJadwalHarian = [
                'STATUS_KELAS' => "Libur",
            ];
            $jadwalHarian = $db->table('jadwal_harian')->update($updateJadwalHarian, ['TANGGAL_JADWAL_HARIAN' => $tanggal]);
        }else{
            $instruktur = $db->table('instruktur')->where('NAMA_INSTRUKTUR', $izin->INSTRUKTUR_PENGGANTI)->get()->getRow();

            $updateJadwalHarian = [
                'ID_INSTRUKTUR' => $instruktur->ID_INSTRUKTUR,
            ];

            $jadwalHarian = $db->table('jadwal_harian')->update($updateJadwalHarian, ['TANGGAL_JADWAL_HARIAN' => $tanggal]);
        }

        if ($query) {
            $izinInstruktur = $db->table('izin_instruktur')->where('ID_IZIN_INSTRUKTUR', $id)->get()->getRow();
            $instruktur = $db->table('instruktur')->where('ID_INSTRUKTUR', $izinInstruktur->ID_INSTRUKTUR)->get()->getRow();

            $izinInstruktur->NAMA_INSTRUKTUR = $instruktur->NAMA_INSTRUKTUR;

            return $this->respondUpdated($izinInstruktur);
        } else {
            return $this->failServerError();
        }
    }

    public function deleteDelete($id = null)
    {
        $db = db_connect();
        $query = $db->table('izin_instruktur')->delete(['ID_IZIN_INSTRUKTUR' => $id]);
        if ($query) {
            return $this->respondDeleted(['id_izin_instruktur' => $id]);
        } else {
            return $this->failServerError();
        }
    }

    private function generateNewId()
    {
        $maxIdQuery = db_connect()->query('SELECT MAX(id_izin_instruktur) as max_id FROM izin_instruktur');
        $maxIdResult = $maxIdQuery->getRow();
        $lastNumber = (int) substr($maxIdResult->max_id ?? 'II000', -3);
        $newNumber = $lastNumber + 1;

        return 'II' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}