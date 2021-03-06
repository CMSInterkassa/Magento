<?php
namespace Magento\InterkassaPayment\Block;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;
/**
 * One page checkout success page
 *
 * @api
 */
class Redirect extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;
    public $payment_systems;
    private $scope;


    const ikUrlSCI = 'https://sci.interkassa.com/';
    const ikUrlAPI = 'https://api.interkassa.com/v1/';

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
      \Magento\Framework\View\Element\Template\Context $context,
      \Magento\Checkout\Model\Session $checkoutSession,
      \Magento\Sales\Model\Order\Config $orderConfig,
      \Magento\Framework\App\Http\Context $httpContext,
      array $data = [],
      \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
      parent::__construct($context, $data);

      $this->_checkoutSession = $checkoutSession;
      $this->_orderConfig = $orderConfig;
      $this->_isScopePrivate = true;
      $this->httpContext = $httpContext;
      $this->scope = $scopeConfig;
    }
    public function ActiveAPI()
    {
      return (bool) $this->getCfg('enable_api');
    }

    public function getOr_ID(){
        return $this->_checkoutSession->getLastRealOrder()->getIncrementId();
    }

    public function getCfg($k)
    {
        return $this->scope->getValue('payment/interkassa_payment/'.$k,\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
    }
    public function getAPIUrl()
    {
        return $this -> getContinueUrl() . 'interkassa/request/api';
    }
    public function getCallBackUrl()
    {
        return $this -> getContinueUrl() . 'interkassa/request/callback';
    }
    public function getSuccessUrl()
    {
        return $this -> getContinueUrl() . 'checkout/onepage/success';
    }
    public function getResponseUrl()
    {
        return $this -> getContinueUrl() . 'interkassa/request/response';
    }
    public function getImage($image)
    {
        return (string) $this->getViewFileUrl('Magento_InterkassaPayment::images/'. $image .'.png');
    }
    public function getAmount($orderId)
    {
        $orderFactory = $this->orderFactory;
        $order = $orderFactory->create()->loadByIncrementId($orderId);
        return $order->getGrandTotal();
    }
    public function getIkPaymentSystems()
    {
        $username = $this->getCfg('api_id');
        $password = $this->getCfg('api_key');
        $remote_url = self::ikUrlAPI . 'paysystem-input-payway?checkoutId=' . $this->getCfg('id_cashbox');

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
    /**
     * Render additional order information lines and return result html
     *
     * @return string
     */
    public function getAdditionalInfoHtml()
    {
        return $this->_layout->renderElement('order.success.additional.info');
    }

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    protected function _beforeToHtml()
    {
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }

    /**
     * Prepares block data
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $order = $this->_checkoutSession->getLastRealOrder();

        //var_dump($this->_checkoutSession);
        $this->addData(
            [
                'is_order_visible' => $this->isVisible($order),
                'print_url' => $this->getUrl(
                    'sales/order/print',
                    ['order_id' => $order->getEntityId()]
                ),
                'can_print_order' => $this->isVisible($order),
                'order_id'  => $order->getIncrementId(),
                'entity_id' => $order->getEntityId()
            ]
        );
    }

    /**
     * Is order visible
     *
     * @param Order $order
     * @return bool
     */
    protected function isVisible(Order $order)
    {
        return !in_array(
            $order->getStatus(),
            $this->_orderConfig->getInvisibleOnFrontStatuses()
        );
    }

    /**
     * Can view order
     *
     * @param Order $order
     * @return bool
     */
    protected function canViewOrder(Order $order)
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH)
            && $this->isVisible($order);
    }

    /**
     * @return string
     * @since 100.2.0
     */
    public function getContinueUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}
