<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль предназначен для CMS Magento 1.9.x
 * @version 1.6
 * @update 10.10.2017
 */

class Interkassa_Interkassa_Block_Response extends Mage_Core_Block_Abstract
{

    protected function _toHtml()
    {
        $interkassa = Mage::getModel('Interkassa/Interkassa');

        $settings = array(
            'ik_co_id' => $interkassa->getConfigData('merchant'),
            'secret_key' => $interkassa->getConfigData('secret_key'),
            'test_key' => $interkassa->getConfigData('test_key')
        );

        if (!empty($_POST) && $this->checkIP() && $settings['ik_co_id'] == $_POST['ik_co_id']) {

            if ($_POST['ik_inv_st'] == 'success') {

                if (isset($_POST['ik_pw_via']) && $_POST['ik_pw_via'] == 'test_interkassa_test_xts') {
                    $secret_key = $settings['test_key'];
                } else {
                    $secret_key = $settings['secret_key'];
                }

                $request_sign = $_POST['ik_sign'];

                $sign = $interkassa->IkSignFormation($_POST, $secret_key);

                if ($request_sign != $sign) {
                    $order = Mage::getModel('sales/order');
                    $order->loadByIncrementId($_POST['ik_pm_no']);
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Цифровая подпись не совпала! Заказ отменен!');

                    $this->wrlog('Подписи не совпадают!');
                } else {
                    //СМЕНА СТАТУСА ЗАКАЗА В АДМИНКЕ В СЛУЧАЕ УСПЕШНОЙ ОПЛАТЫ
                    $order = Mage::getModel('sales/order');
                    $order->loadByIncrementId($_POST['ik_pm_no']);
                    $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true, 'Заказ был оплачен с помощью Интеркасса.');
                    //$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Заказ был оплачен с помощью Интеркасса.');

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

    public function selectPaySystem()
    {
        $interkassa = Mage::getModel('Interkassa/Interkassa');
        $secret_key = $interkassa->getConfigData('secret_key');

        if (isset($_POST['ik_act']) && $_POST['ik_act'] == 'process')
            return $interkassa->getAnswerFromAPI($_POST);
        else
            return $interkassa->IkSignFormation($_POST, $secret_key);
    }

    public function wrlog($content)
    {//Функция для ведения лога
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