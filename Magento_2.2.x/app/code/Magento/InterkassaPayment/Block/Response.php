<?php
namespace Magento\InterkassaPayment\Block;
class Response extends \Magento\Framework\View\Element\Template
{
    public function __construct(\Magento\Framework\View\Element\Template\Context $context)
    {
        parent::__construct($context);
    }

    public function msgSuccess()
    {
        return __('Successful payment order #');
    }

    public function msgFail()
    {
        return __('Unsuccessful payment order #');
    }
}