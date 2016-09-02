<?php
/**
 * Модуль разработан в компании GateOn предназначен для CMS Magento 1.9
 * Сайт разработчикa: www.gateon.net
 * E-mail: www@smartbyte.pro
 * Версия: 1.4
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

        //Вывод всего ответа Интеркассы в log.txt(в корне сайта) по желанию
        foreach ($_REQUEST as $key => $value) {
            $str = $key . ' => ' . $value;
            $this->wrlog($str);
        }
        $this->wrlog('--------');


        if (count($_REQUEST) && $settings['ik_co_id'] == $_REQUEST['ik_co_id']) {

            $this->wrlog('params ok');

            if ($_REQUEST['ik_inv_st'] == 'success') {

                $this->wrlog('success');

                if (isset($_REQUEST['ik_pw_via']) && $_REQUEST['ik_pw_via'] == 'test_interkassa_test_xts') {
                    $secret_key = $settings['test_key'];
                } else {
                    $secret_key = $settings['secret_key'];
                }
                $this->wrlog($secret_key);

                $request_sign = $_REQUEST['ik_sign'];

                $dataSet = [];

                foreach ($_REQUEST as $key => $value) {
                    if (!preg_match('/ik_/', $key)) continue;
                    $dataSet[$key] = $value;
                }

                unset($dataSet['ik_sign']);
                ksort($dataSet, SORT_STRING);
                array_push($dataSet, $secret_key);
                $signString = implode(':', $dataSet);
                $sign = base64_encode(md5($signString, true));

                if ($request_sign != $sign) {
                    $this->wrlog('Подписи не совпадают!');

                } else {
                    $this->wrlog('Подписи совпадают!');
                    //СМЕНА СТАТУСА ЗАКАЗА В АДМИНКЕ В СЛУЧАЕ УСПЕШНОЙ ОПЛАТЫ
                    $order = Mage::getModel('sales/order');
                    $order->loadByIncrementId($_POST['ik_pm_no']);
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');

                    $order->sendNewOrderEmail();
                    $order->setEmailSent(true);

                    $order->save();

                    Mage::getSingleton('checkout/session')->unsQuoteId();

                    $url = Mage::getUrl('checkout/onepage/success', array('_secure' => true));
                    Mage::app()->getFrontController()->getResponse()->setRedirect($url);
                }
            }

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
}