<?php

/**
 * @name Интеркасса 2.0
 * @description Модуль разработан в компании GateOn предназначен для CMS Magento 1.9.2.4
 * @author www.gateon.net
 * @email www@smartbyte.pro
 * @version 1.5
 * @update 25.10.2016
 */

class InterkassaForm
{
    const ORDER_APPROVED = 'approved';
    const ORDER_DECLINED = 'declined';

    const ORDER_SEPARATOR = '#';

    const SIGNATURE_SEPARATOR = '|';

    const URL = "https://sci.interkassa.com/";

    protected static $responseFields = array('rrn',
        'masked_card',
        'sender_cell_phone',
        'response_status',
        'currency',
        'fee',
        'reversal_amount',
        'settlement_amount',
        'actual_amount',
        'order_status',
        'response_description',
        'order_time',
        'actual_currency',
        'order_id',
        'tran_type',
        'eci',
        'settlement_date',
        'payment_system',
        'approval_code',
        'merchant_id',
        'settlement_currency',
        'payment_id',
        'sender_account',
        'card_bin',
        'response_code',
        'card_type',
        'amount',
        'sender_email');

    public static function getSignature($data, $password, $encoded = true)
    {
        $data = array_filter($data, function($var) {
            return $var !== '' && $var !== null;
        });
        ksort($data);

        $str = $password;
        foreach ($data as $k => $v) {
            $str .= self::SIGNATURE_SEPARATOR . $v;
        }

        if ($encoded) {
            return sha1($str);
        } else {
            return $str;
        }
    }

    public static function isPaymentValid($oplataSettings, $response)
    {
        
    }

    public function wrlog($content){
        $file = 'log.txt';
        $doc = fopen($file, 'a');
        file_put_contents($file, PHP_EOL . $content, FILE_APPEND);
        fclose($doc);
    }
}
