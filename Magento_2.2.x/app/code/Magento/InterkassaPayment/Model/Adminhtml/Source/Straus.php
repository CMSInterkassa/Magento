<?php
namespace Magento\InterkassaPayment\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 */
class Straus implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */

   public function __construct(\Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusCollectionFactory)
   {
       $this->statusCollectionFactory = $statusCollectionFactory;
   }

    public function toOptionArray()
    {
        return $this->statusCollectionFactory->create()->toOptionArray();
    }
}
