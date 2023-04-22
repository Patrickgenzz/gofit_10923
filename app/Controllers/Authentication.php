<?php

namespace App\Controllers;

class Authentication extends BaseController
{
    public function postLogin()
    {
        $db = db_connect();
        $data = $this->request->getJSON();

        $results = $db->query(
            'SELECT ROLE as user_type, EMAIL, PASSWORD FROM pegawai WHERE EMAIL="' . $data->EMAIL . '"
            UNION ALL
            SELECT "MEMBER" as user_type, EMAIL, PASSWORD FROM member WHERE EMAIL="' . $data->EMAIL . '"
            UNION ALL
            SELECT "INSTRUKTUR" as user_type, EMAIL, PASSWORD FROM instruktur WHERE EMAIL="' . $data->EMAIL . '";'
        )->getResultArray();

        if(count($results) > 0){
            foreach($results as $result){
                if(password_verify($data->PASSWORD, $result['PASSWORD'])){
                    return $this->respond([$result['user_type'] => $result], 200);
                }
            }
        }
        return $this->failUnauthorized();
    }
}