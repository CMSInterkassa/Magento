<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль разработан в компании GateOn предназначен для CMS Magento 1.9.2.4
 * @author www.gateon.net
 * @email www@smartbyte.pro
 * @version 1.5
 * @update 25.10.2016
 */


class Interkassa_Interkassa_Block_Response extends Mage_Core_Block_Abstract
{

    protected function _toHtml()
    {

        include_once "Interkassa.cls.php";
        $oplata = Mage::getModel('Interkassa/Interkassa');

        $settings = array(
            'ik_co_id' => $oplata->getConfigData('merchant'),
            'secret_key' => $oplata->getConfigData('secret_key'),
            'test_key' => $oplata->getConfigData('test_key')
        );

        if (count($_POST) && $this->checkIP() && $settings['ik_co_id'] == $_POST['ik_co_id']) {

            if ($_POST['ik_inv_st'] == 'success') {

                if (isset($_POST['ik_pw_via']) && $_POST['ik_pw_via'] == 'test_interkassa_test_xts') {
                    $secret_key = $settings['test_key'];
                } else {
                    $secret_key = $settings['secret_key'];
                }

                $request_sign = $_POST['ik_sign'];

                $dataSet = [];
                foreach ($_POST as $key => $value) {
                    if (!preg_match('/ik_/', $key)) continue;
                    $dataSet[$key] = $value;
                }

                unset($dataSet['ik_sign']);
                ksort($dataSet, SORT_STRING);
                array_push($dataSet, $secret_key);
                $signString = implode(':', $dataSet);
                $sign = base64_encode(md5($signString, true));

                if ($request_sign != $sign) {
                    $order = Mage::getModel('sales/order');
                    $order->loadByIncrementId($_POST['ik_pm_no']);
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Цифровая подпись не совпала! Заказ отменен!');

                } else {
                    $this->wrlog('Подписи совпадают!');
                    //СМЕНА СТАТУСА ЗАКАЗА В АДМИНКЕ В СЛУЧАЕ УСПЕШНОЙ ОПЛАТЫ
                    $order = Mage::getModel('sales/order');
                    $order->loadByIncrementId($_POST['ik_pm_no']);
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Заказ был оплачен с помощью Интеркасса.');

                    $order->sendNewOrderEmail();
                    $order->setEmailSent(true);

                    $order->save();

                    Mage::getSingleton('checkout/session')->unsQuoteId();

                    $url = Mage::getUrl('checkout/onepage/success', array('_secure' => true));
                    Mage::app()->getFrontController()->getResponse()->setRedirect($url);
                }
            }

        }else{
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($_POST['ik_pm_no']);
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Ответ Интеркассы неправильный! Заказ отменен!');
        }

    }

//Функция для ведения лога
    public function wrlog($content)
    {
        $file = 'log.txt';
        $doc = fopen($file, 'a');
        file_put_contents($file, PHP_EOL . $content, FILE_APPEND);
        fclose($doc);
    }
    public function checkIP(){
        $ip_stack = array(
            'ip_begin'=>'151.80.190.97',
            'ip_end'=>'151.80.190.104'
        );

        if(!ip2long($_SERVER['REMOTE_ADDR'])>=ip2long($ip_stack['ip_begin']) && !ip2long($_SERVER['REMOTE_ADDR'])<=ip2long($ip_stack['ip_end'])){
            $this->wrlog('REQUEST IP'.$_SERVER['REMOTE_ADDR'].'doesnt match');
            die('Ты мошенник! Пшел вон отсюда!');
        }
        return true;
    }
}