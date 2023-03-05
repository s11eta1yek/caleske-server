<?php

namespace App\Helpers;

class IdPay
{
    public static function payment($parameter)
    {
        $params = [
            'order_id' => $parameter['order_id'],
            'amount' => $parameter['amount'],
            'name' => $parameter['name'] ?? '',
            'phone' => $parameter['phone'] ?? '',
            'mail' => $parameter['email'] ?? '',
            'desc' => env('DESCRIPTION'),
            'callback' => env('CALLBACK'),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.idpay.ir/v1.1/payment');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-API-KEY:' . env('ID_PAY') ,
            'X-SANDBOX: 1'
        ));

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result);
    }

    public static function verify($parameter)
    {
        $params = [
            'id' => $parameter['id'],
            'order_id' => $parameter['order_id'],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.idpay.ir/v1.1/payment/verify');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-API-KEY:' . env('ID_PAY'),
            'X-SANDBOX: 1',
        ));

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result);
    }
}
