<?php

namespace App\Helpers;

class Sms
{
   static function send($receptor , $params, $template = 'Verify-kaleske') {
        $url='https://api.kavenegar.com/v1/'. env('KavehnegarKey') .'/verify/lookup.json?receptor=' . $receptor . '&token=' . $params . '&template=' . $template;
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        //curl_setopt($ch,CURLOPT_POSTFIELDS);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $res = curl_exec($ch);
        $res = json_decode($res);
        if(isset($res->return))
            return $res->return;
        else
            return false;
    }
}
