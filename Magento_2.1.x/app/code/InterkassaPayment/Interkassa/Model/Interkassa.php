<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль разработан в компании GateOn предназначен для CMS Magento 2.1.x
 * @version 1.0
 */
namespace InterkassaPayment\Interkassa\Model;

use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;


/**
 * Pay In Store payment method model
 */
class Interkassa extends AbstractMethod
{
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'interkassa';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * Payment additional info block
     *
     * @var string
     */
    protected $_formBlockType = 'InterkassaPayment\Interkassa\Block\Form\Interkassa';

    /**
     * Sidebar payment info block
     *
     * @var string
     */
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

    /**
     * interaction url
     *
     * @var string
     */
    protected $_actionUrl = "https://sci.interkassa.com/";

    protected $_checkoutSession;

    /**
     * test
     *
     * @var bool
     */
    protected $_test;

    protected $orderFactory;


    const ikUrlSCI = 'https://sci.interkassa.com/';
    const ikUrlAPI = 'https://api.interkassa.com/v1/';

    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = [])
    {
        $this->orderFactory = $orderFactory;

        $this->_checkoutSession = $checkoutSession;

        parent::__construct($context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data);
    }


    public function getAmount($orderId)
    {
        $orderFactory = $this->orderFactory;
        $order = $orderFactory->create()->loadByIncrementId($orderId);
        return $order->getGrandTotal();
    }

    protected function getOrder($orderId)
    {
        $orderFactory = $this->orderFactory;
        return $orderFactory->create()->loadByIncrementId($orderId);

    }

    public function initialize($paymentAction, $stateObject)
    {
        $this->_actionUrl = $this->getConfigData('action_url');
        $this->_test = $this->getConfigData('test');
        $stateObject->setState(Order::STATE_NEW);
        $stateObject->setStatus(Order::STATE_NEW);
        $stateObject->setIsNotified(false);
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return true;
    }


    public function getActionUrl()
    {
        return $this->_actionUrl;

    }

    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    public function isAPIAvailable()
    {
        return $this->getConfigData('activeAPI');
    }

    protected function isCarrierAllowed($shippingMethod)
    {
        return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== false;
    }

    public function getCurrencyCode($orderId)
    {
        return $this->getOrder($orderId)->getBaseCurrencyCode();
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

    public function getAnswerFromAPI($data)
    {
        $ch = curl_init(self::ikUrlSCI);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        return $result;
    }

    public function getPostData($orderId)
    {
        $FormData = [];
        $FormData['ik_am'] = round($this->getAmount($orderId), 2);
        $FormData['ik_pm_no'] = intval($orderId);
        $FormData['ik_co_id'] = $this->getConfigData('ik_co_id');
        $FormData['ik_desc'] = "Payment for order " . $orderId;
        $FormData['ik_cur'] = $this->getCurrencyCode($orderId);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $baseurl = $storeManager->getStore()->getBaseUrl();

        $FormData['ik_ia_u'] = $baseurl . 'interkassa/request/request';
        $FormData['ik_suc_u'] = $baseurl . 'checkout/onepage/success';
        $FormData['ik_fal_u'] = $baseurl;
        $FormData['ik_pnd_u'] = $baseurl;

        if ($this->getConfigData('test') == 1) {
            $FormData['ik_pw_via'] = 'test_interkassa_test_xts';
        }

        $FormData['ik_sign'] = $this->IkSignFormation($FormData, $this->getConfigData('secret_key'));

        return $FormData;
    }

    public function process($ik_response)
    {
        $this->wrlog($ik_response);
        if (
            count($ik_response) > 5 && $this->checkIP() &&
            isset($ik_response['ik_sign']) &&
            isset($ik_response['ik_pm_no']) &&
            isset($ik_response['ik_am']) &&
            isset($ik_response['ik_co_id']) &&
            $ik_response['ik_co_id'] == $this->getConfigData('ik_co_id')
        ) {

            $debugData = ['response' => $ik_response];
            $this->_debug($debugData);

            $order = $this->getOrder((int)$ik_response['ik_pm_no']);

            if ($order) {
                $result = $this->_processOrder($order, $ik_response);
                $this->wrlog($result);
            } else {
                $this->wrlog('order № incorrect');
            }
        } else {
            $this->wrlog('something wrong');
        }
    }

    protected function _processOrder(\Magento\Sales\Model\Order $order, $response)
    {
        $payment = $order->getPayment();

        $errors = array();

        if ($response['ik_pw_via'] == 'test_interkassa_test_xts') {
            $key = $this->getConfigData('test_key');
        } else {
            $key = $this->getConfigData('secret_key');
        }

        $request_sign = $response['ik_sign'];
        $verified_sign = $this->IkSignFormation($response, $key);

        $order_amount = (int)$order->getGrandTotal();

        if ($order_amount != $response["ik_am"]) {
            $errors[] = "Incorrect Amount: " . $response["OutSum"] . " (need: " . $order_amount . ")";
        } else {
            if ($request_sign == $verified_sign) {
                $payment->setTransactionId($response["ik_pm_no"])->setIsTransactionClosed(0);
                $order->setStatus(Order::STATE_PROCESSING);
                $order->setState(Order::STATE_PROCESSING);
                $order->save();
                return "Ok! Order №" . $response["ik_pm_no"] . "Paid";
            } else {
                $errors[] = "Incorrect HASH (need:" . $verified_sign . ", got:" . $request_sign . "- fraud data or wrong secret Key";
                $errors[] = "Maybe success payment";
                return $errors;
            }
        }
    }


    public function getPaymentSystems()
    {
        $username = $this->getConfigData('api_id');
        $password = $this->getConfigData('api_key');
        $remote_url = self::ikUrlAPI . 'paysystem-input-payway?checkoutId=' . $this->getConfigData('ik_co_id');

        $businessAcc = $this->getIkBusinessAcc($username, $password);

        $ikHeaders = [];
        $ikHeaders[] = "Authorization: Basic " . base64_encode("$username:$password");
        if(!empty($businessAcc)) {
            $ikHeaders[] = "Ik-Api-Account-Id: " . $businessAcc;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $ikHeaders);
        $response = curl_exec($ch);

        if(empty($response))
            return '<strong style="color:red;">Error!!! System response empty!</strong>';

        $json_data = json_decode($response);
        if ($json_data->status != 'error') {
            $payment_systems = array();
            if(!empty($json_data->data)){
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
            }

            return !empty($payment_systems)? $payment_systems : '<strong style="color:red;">API connection error or system response empty!</strong>';
        } else {
            if(!empty($json_data->message))
                return '<strong style="color:red;">API connection error!<br>' . $json_data->message . '</strong>';
            else
                return '<strong style="color:red;">API connection error or system response empty!</strong>';
        }
    }

    public function getIkBusinessAcc($username = '', $password = '')
    {
        $tmpLocationFile = __DIR__ . '/tmpLocalStorageBusinessAcc.ini';
        $dataBusinessAcc = function_exists('file_get_contents')? file_get_contents($tmpLocationFile) : '{}';
        $dataBusinessAcc = json_decode($dataBusinessAcc, 1);
        $businessAcc = is_string($dataBusinessAcc['businessAcc'])? trim($dataBusinessAcc['businessAcc']) : '';
        if(empty($businessAcc) || sha1($username . $password) !== $dataBusinessAcc['hash']) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, self::ikUrlAPI . 'account');
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode("$username:$password")]);
            $response = curl_exec($curl);

            if (!empty($response['data'])) {
                foreach ($response['data'] as $id => $data) {
                    if ($data['tp'] == 'b') {
                        $businessAcc = $id;
                        break;
                    }
                }
            }

            if(function_exists('file_put_contents')){
                $updData = [
                    'businessAcc' => $businessAcc,
                    'hash' => sha1($username . $password)
                ];
                file_put_contents($tmpLocationFile, json_encode($updData, JSON_PRETTY_PRINT));
            }

            return $businessAcc;
        }

        return $businessAcc;
    }

    public function wrlog($content)
    {
        $file = 'var/log/iklog.txt';
        $doc = fopen($file, 'a');
        if ($doc) {
            file_put_contents($file, PHP_EOL . '====================' . date("H:i:s") . '=====================', FILE_APPEND);
            if (is_array($content)) {
                $this->wrlog('Вывод массива:');
                foreach ($content as $k => $v) {
                    if (is_array($v)) {
                        $this->wrlog($v);
                    } else {
                        file_put_contents($file, PHP_EOL . $k . '=>' . $v, FILE_APPEND);
                    }
                }
            } elseif (is_object($content)) {
                $this->wrlog('Вывод обьекта:');
                foreach (get_object_vars($content) as $k => $v) {
                    if (is_object($v)) {
                        $this->wrlog($v);
                    } else {
                        file_put_contents($file, PHP_EOL . $k . '=>' . $v, FILE_APPEND);
                    }
                }
            } else {
                file_put_contents($file, PHP_EOL . $content, FILE_APPEND);
            }
            fclose($doc);
        }
    }

    public function checkIP()
    {
        $ip_stack = array(
            'ip_begin' => '151.80.190.97',
            'ip_end' => '151.80.190.104'
        );

        if (ip2long($_SERVER['REMOTE_ADDR']) < ip2long($ip_stack['ip_begin']) || ip2long($_SERVER['REMOTE_ADDR']) > ip2long($ip_stack['ip_end'])) {
            $this->wrlog('REQUEST IP' . $_SERVER['REMOTE_ADDR'] . 'doesnt match');
            die('Ты мошенник! Пшел вон отсюда!');
        }
        return true;
    }

}
