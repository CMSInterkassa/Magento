<div class="interkasssa" style="text-align: center;">
    <img style="display: inline-block;" src="/app/code/local/Interkassa/Interkassa/Block/logo_interkassa.png"/>
</div>

<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль разработан в компании GateOn предназначен для CMS Magento 1.9.2.4
 * @author www.gateon.net
 * @email www@smartbyte.pro
 * @version 1.5
 * @update 25.10.2016
 */


class Interkassa_Interkassa_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {

        include_once "Interkassa.cls.php";
        $oplata = Mage::getModel('Interkassa/Interkassa');

        $data = $oplata->getFormFields();

        $state = $oplata->getConfigData('order_status');

        $order = $oplata->getQuote();

        $secret_key = $oplata->getConfigData('secret_key');

        $order->setStatus($state);
        $order->save();


        if($data['fields']['ik_cur'] == 'RUR'){
            $currency = 'RUB';
        }else{
            $currency = $data['fields']['ik_cur'];
        }

        $arg = array(
            'ik_cur'=>$currency,
            'ik_co_id'=>$data['fields']['ik_co_id'],
            'ik_pm_no'=>$data['fields']['ik_pm_no'],
            'ik_am'=>$data['fields']['ik_am'],
            'ik_desc'=>$data['fields']['ik_desc'],
        );

        $dataSet = $arg;
        ksort($dataSet, SORT_STRING);
        array_push($dataSet, $secret_key);
        $signString = implode(':', $dataSet);
        $sign = base64_encode(md5($signString, true));



        $html ='<form name="payment" id="InterkassaForm" action="https://sci.interkassa.com/" method="POST">';

        $html .= '<input type="hidden" name="ik_co_id" value="'.$data['fields']['ik_co_id'].'">';
        $html .= '<input type="hidden" name="ik_cur" value="'.$currency.'">';
        $html .= '<input type="hidden" name="ik_am" value="'.$data['fields']['ik_am'].'">';
        $html .= '<input type="hidden" name="ik_pm_no" value="'.$data['fields']['ik_pm_no'].'">';
        $html .= '<input type="hidden" name="ik_desc" value="'.$data['fields']['ik_desc'].'">';
        $html .= '<input type="hidden" name="ik_sign" value="'.$sign.'">';
        $html .= '</form>';
        $html .= '<script type="text/javascript">document.getElementById("InterkassaForm").submit();</script>';


        return $html;
    }
}
