<?php

namespace IDP\CustomApiField\Observer\Sales;

use Magento\Framework\Event\ObserverInterface;

class OrderLoadAfter implements ObserverInterface 
{
 
    public function execute(\Magento\Framework\Event\Observer $observer) 
    {
        $order = $observer->getOrder();
        $extensionAttributes = $order->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->getOrderExtensionDependency();
        }
        
        // Set up DB connection
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        // By default, hold date isblank
        $shipping_hold_date = "";

        $method = $order->getData('shipping_method');

        if ( substr( $method, 0, 19) == 'amstrates_amstrates' )
        {
            // Whatever follows is the ID from amasty_table_method
            $id = substr($method,18);

            if ( $id )
            {
                // Select comment from shipping table
                $sql = "SELECT comment FROM amasty_table_method WHERE id = :id";
                $shipping_hold_date = $connection->fetchOne($sql, [':id' => $id]);
            }

        }

        // Add the shipping hold date to the extension attribute
        $extensionAttributes->setShippingHoldDate($shipping_hold_date);
        $order->setExtensionAttributes($extensionAttributes);
    }
 
    private function getOrderExtensionDependency()    
    {
        $orderExtension = \Magento\Framework\App\ObjectManager::getInstance()->get(
            '\Magento\Sales\Api\Data\OrderExtension'
        );
        return $orderExtension;
    }
 
}