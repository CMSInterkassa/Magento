<?php

namespace InterkassaPayment\Interkassa\Controller\Request;

use \Magento\Framework\App\Action\Context;

use \Magento\Framework\App\Request\Http;

use \Magento\Sales\Model\OrderFactory;

use \Magento\Framework\View\Result\PageFactory;

use InterkassaPayment\Interkassa\Model\Interkassa;

class Request extends \Magento\Framework\App\Action\Action
{
    protected $urlBuilder;

    public $request;

    public $storeManager;

    public $objectManager;

    public $baseurl;

    public $order;

    public $orderFactory;

    protected $resultPageFactory;


    public function __construct(

        Context $context,
        Http $request,
        PageFactory $resultPageFactory,
        OrderFactory $orderFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->orderFactory = $orderFactory;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->storeManager = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $this->baseurl = $this->storeManager->getStore()->getBaseUrl();
        $this->request = $request;
        return parent::__construct($context);
    }

    public function execute()
    {
        $paymentMethod = $this->_objectManager->create('InterkassaPayment\Interkassa\Model\Interkassa');
        $request = $this->request->getPost();
        $paymentMethod->process($request);
    }

    public function getPost()
    {
        echo $this->baseurl . 'interkassa/request/request';
        return $this->request->getPost();
    }

    public function wrlog($content)
    {
        $file = 'log.txt';
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
}