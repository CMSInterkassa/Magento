<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль разработан в компании GateOn предназначен для CMS Magento 2.1.x
 * @author www.gateon.net
 * @email www@smartbyte.pro
 * @version 1.0
 */
namespace InterkassaPayment\Interkassa\Block\Form;

abstract class Interkassa extends \Magento\Payment\Block\Form
{
    protected $_instructions;

    protected $_template = 'form/interkassa.phtml';

    public function getInstructions()
    {
        if ($this->_instructions === null) {
            $method = $this->getMethod();
            $this->_instructions = $method->getConfigData('instructions');
        }
        return $this->_instructions;
    }
}
