<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль предназначен для CMS Magento 1.9.x
 * @version 1.6
 * @update 10.10.2017
 */

class Interkassa_Interkassa_Model_Interkassa extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'Interkassa';
    protected $_formBlockType = 'Interkassa/form';

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('Interkassa/redirect', array('_secure' => true));
    }

    public function getQuote()
    {
        $orderIncrementId = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        return $order;
    }

    public function getDataFormIK($orderId)
    {
        $order_id = ((int)$orderId > 0)? $orderId : $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

        $FormData = array();
        $FormData['ik_am'] = round($order->getGrandTotal(), 2);
        $FormData['ik_pm_no'] = intval($order_id);
        $FormData['ik_co_id'] = $this->getConfigData('merchant');
        $FormData['ik_desc'] = "Payment for order #" . $order_id;
        $FormData['ik_cur'] = $order->order_currency_code;

        $baseurl = Mage::getBaseUrl();

        $FormData['ik_ia_u'] = Mage::getUrl('interkassa/response/');
        $FormData['ik_suc_u'] = $baseurl . 'checkout/onepage/success';
        $FormData['ik_fal_u'] = $baseurl;
        $FormData['ik_pnd_u'] = $baseurl;

        if($FormData['ik_cur'] == 'RUR')
            $FormData['ik_cur'] = 'RUB';

        $secret_key = $this->getConfigData('secret_key');

        if ($this->getConfigData('test_mode') == 1) {
            $FormData['ik_pw_via'] = 'test_interkassa_test_xts';
            $secret_key = $this->getConfigData('test_key');
        }

        $FormData['ik_sign'] = $this->IkSignFormation($FormData, $secret_key);

        return $FormData;
    }

    public function IkSignFormation($data, $secret_key)
    {
        if (!empty($data['ik_sign'])) unset($data['ik_sign']);

        $dataSet = array();
        foreach ($data as $key => $value) {
            if (!preg_match('/ik_/', $key)) continue;
            $dataSet[$key] = $value;
        }

        ksort($dataSet, SORT_STRING);
        array_push($dataSet, $secret_key);
        $arg = implode(':', $dataSet);
        $ik_sign = base64_encode(md5($arg, true));

        return $ik_sign;
    }

    public function isActiveAPI(){
        if($this->getConfigData('active_api'))
            return true;
        else
            return false;
    }

    public function getAnswerFromAPI($data)
    {
        $ch = curl_init('https://sci.interkassa.com/');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        return $result;
    }

    public function getPaymentSystems()
    {
        $username = $this->getConfigData('api_id');
        $password = $this->getConfigData('api_key');
        $remote_url = 'https://api.interkassa.com/v1/paysystem-input-payway?checkoutId=' . $this->getConfigData('merchant');

        // Create a stream
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Authorization: Basic " . base64_encode("$username:$password")
            )
        );

        $context = stream_context_create($opts);
        $file = file_get_contents($remote_url, false, $context);
        $json_data = json_decode($file);

        if ($json_data->status != 'error') {
            $payment_systems = array();
            foreach ($json_data->data as $ps => $info) {
                $payment_system = $info->ser;
                if (!array_key_exists($payment_system, $payment_systems)) {
                    $payment_systems[$payment_system] = array();
                    foreach ($info->name as $name) {
                        if ($name->l == 'en') {
                            $payment_systems[$payment_system]['title'] = ucfirst($name->v);
                        }
                        $payment_systems[$payment_system]['name'][$name->l] = $name->v;

                    }
                }
                $payment_systems[$payment_system]['currency'][strtoupper($info->curAls)] = $info->als;

            }
            return $payment_systems;
        } else {
            return '<strong style="color:red;">API connection error!<br>' . $json_data->message . '</strong>';
        }
    }
}


