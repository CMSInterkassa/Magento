<?php

namespace InterkassaPayment\Interkassa\Controller\Request;


use Magento\Framework\App\Action\Context;

use \Magento\Framework\App\Request\Http;

use \InterkassaPayment\Interkassa\Model\Interkassa;

use Magento\Config\Model\ResourceModel\Config;

class API extends \Magento\Framework\App\Action\Action
{
    public $http;
    protected $interkassa;

    public function getRequest()
    {
        return parent::getRequest(); // TODO: Change the autogenerated stub
    }

    public function __construct(
        Context $context,
        Http $http,
        Interkassa $interkassa
    )
    {
        $this->interkassa = $interkassa;
        $this->http = $http;
        parent::__construct($context);
    }
    public function execute()
    {
        echo $this->getIkSign();
        exit;
    }

    public function getIkSign(){
       $post = $this->getPost();

        if($post){
            if(isset($post['ik_act']) && $post['ik_act'] == 'process')
                return $this->interkassa->getAnswerFromAPI($post);
            else
                return $this->interkassa->IkSignFormation($post,$this->interkassa->getConfigData('secret_key'));
        }else{
            return array(
                'error'=>'something wrong in Sign Formation'
            );
        }
    }
    public function getPost(){
        if(!empty($_POST) && !empty($_POST['ik_co_id'])){
            return $this->http->getPost();
        }else{
            return false;
        }

    }

}