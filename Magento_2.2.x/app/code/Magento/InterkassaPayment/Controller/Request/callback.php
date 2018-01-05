<?php
namespace Magento\InterkassaPayment\Controller\Request;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Request\Http;
use \Magento\Sales\Model\OrderFactory;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Sales\Model\Order;

class callback extends \Magento\Framework\App\Action\Action
{
  protected $urlBuilder;
  public $request;
  public $storeManager;
  public $objectManager;
  public $baseurl;
  public $order;
  public $orderFactory;
  protected $resultPageFactory;
  public $scope;

  public function __construct(
    Context $context,
    Http $request,
    PageFactory $resultPageFactory,
    OrderFactory $orderFactory,
    ScopeConfigInterface $scopeConfig
  )
  {
    $this->resultPageFactory = $resultPageFactory;
    $this->orderFactory = $orderFactory;
    $this->request = $request;
    $this->scope = $scopeConfig;
    return parent::__construct($context);
  }

  public function execute()
  {
      //file_put_contents(__DIR__.'/gg.txt', "\n params \n" . json_encode(array($_POST, $_GET, $_REQUEST, $_SERVER),JSON_PRETTY_PRINT),FILE_APPEND);
      $ik_response = $this->request->getParams();

      if ($this->checkIP() && isset($ik_response['ik_pm_no']) && isset($ik_response['ik_co_id'])
          && $ik_response['ik_co_id'] == $this->getCfg('id_cashbox')) {
          $order = \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Sales\Model\Order')->loadByIncrementId((int)$ik_response['ik_pm_no']);
          if ($order) {
              $result = $this->_processOrder($order, $ik_response);
          } else {
              echo 'order № incorrect';
          }
      } else {
          die('Hack! Go out to away!');
      }
      exit;
  }

  protected function _processOrder(\Magento\Sales\Model\Order $order, $response)
  {
      if(empty($order)) return;

      $payment = $order->getPayment();

      if ($response['ik_pw_via'] == 'test_interkassa_test_xts')
           $key = $this->getCfg('test_key');
      else
           $key = $this->getCfg('secret_key');

      $request_sign = $response['ik_sign'];
      $verified_sign = $this->IkSignFormation($response, $key);

      $order_amount = round($order->getGrandTotal(), 2);
      $ik_am = round($response["ik_am"], 2);

      if ($order_amount == $ik_am) {
          if ($request_sign == $verified_sign) {
              //$payment->setTransactionId($response["ik_pm_no"])->setIsTransactionClosed(0); // !!!!!!!!!!!!!!!!!!!
              // $response['ik_inv_id']

              switch ($response['ik_inv_st']) {
                case 'success':
                    $ik_status_ok = $this->getCfg('ik_status_ok');
                    $ik_status_ok = !empty($ik_status_ok)? $ik_status_ok : Order::STATE_COMPLETE;
                    $order->setStatus($ik_status_ok);
                    $order->setState($ik_status_ok);
                break;
                case 'fail':
                case 'canceled':
                  $order->setStatus(Order::STATE_CANCELED);
                  $order->setState(Order::STATE_CANCELED);
                break;
              }
              $order->save();

          } else
              return 'lol, is hack!!!?';
      }
  }

  private function getCfg($k)
  {
    return $this->scope->getValue('payment/interkassa_payment/'.$k,\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
  }

  private function IkSignFormation($data, $secret_key)
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

  public function checkIP()
  {
      $ip_stack = array(
          'ip_begin' => '151.80.190.97',
          'ip_end' => '151.80.190.104'
      );

      $ip = ip2long($_SERVER['REMOTE_ADDR'])? ip2long($_SERVER['REMOTE_ADDR']) : !ip2long($_SERVER['REMOTE_ADDR']);
      if(($ip >= ip2long($ip_stack['ip_begin'])) && ($ip <= ip2long($ip_stack['ip_end'])))
            return true;
      else
          return false;
  }
}
