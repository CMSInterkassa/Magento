<?php
namespace Magento\InterkassaPayment\Controller\Request;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Request\Http;
use \Magento\Sales\Model\OrderFactory;
use \Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;


class api extends \Magento\Framework\App\Action\Action
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
      \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
  )
  {
      $this->resultPageFactory = $resultPageFactory;
      $this->orderFactory = $orderFactory;
      $this->request = $request;
      $this->scope = $scopeConfig;
      return parent::__construct($context);
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

  private function getAnswerFromAPI($data)
  {
    $ch = curl_init('https://sci.interkassa.com/');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    return $result;
  }

  public function execute()
  {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') die();

    $data_post = $this->request->getPost();
    switch ($_GET['get']) {
      case 'sign':
        ob_clean();
        echo json_encode(array('sign' => $this->IkSignFormation($data_post, $this->getCfg('secret_key'))));
        break;
      case 'ans':
        $sign = $this->IkSignFormation($data_post, $this->getCfg('secret_key'));
        $data_post['ik_sign'] = $sign;
        ob_clean();
        echo $this->getAnswerFromAPI($data_post);
        break;
      default:
        die();
    }
    exit;
  }

  private function getCfg($k)
  {
    return $this->scope->getValue('payment/interkassa_payment/' . $k, \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
  }
}
