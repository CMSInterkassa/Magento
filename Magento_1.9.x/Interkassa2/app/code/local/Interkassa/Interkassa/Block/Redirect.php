<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль предназначен для CMS Magento 1.9.x
 * @version 1.6
 * @update 10.10.2017
 */

class Interkassa_Interkassa_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $model = Mage::getModel('Interkassa/Interkassa');

        $state = $model->getConfigData('order_status');

        $order = $model->getQuote();
        $order->setStatus($state);
        $order->save();

        return $model;
    }
}
