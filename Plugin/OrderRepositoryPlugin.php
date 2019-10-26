<?php  
namespace IDP\CustomApiField\Plugin;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class OrderRepositoryPlugin
 */
class OrderRepositoryPlugin
{
 
    /**
     * Order Extension Attributes Factory
     *
     * @var OrderExtensionFactory
     */
    protected $extensionFactory;
 
    /**
     * OrderRepositoryPlugin constructor
     *
     * @param OrderExtensionFactory $extensionFactory
     */
    public function __construct(OrderExtensionFactory $extensionFactory)
    {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * Add "shipping_hold_date" extension attribute to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     *
     * @return OrderInterface
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order)
    {
        $shipping_hold_date = $this->loadShipHoldDate($order);

        $extensionAttributes = $order->getExtensionAttributes();
        $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();

        $extensionAttributes->setShippingHoldDate($shipping_hold_date);
        $order->setExtensionAttributes($extensionAttributes);
        return $order;
    }

    /**
     * Add "external_order_id" extension attribute to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResult
     *
     * @return OrderSearchResultInterface
     */
    public function afterGetList(OrderRepositoryInterface $subject, OrderSearchResultInterface $searchResult)
    {   $orders = $searchResult->getItems();
        foreach ($orders as &$order) {
            $shipping_hold_date = $this->loadShipHoldDate($order);

            $extensionAttributes = $order->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();

            $extensionAttributes->setShippingHoldDate($shipping_hold_date);
            $order->setExtensionAttributes($extensionAttributes);
        }
        return $searchResult;
    }

    public function loadShipHoldDate($order)
    {
        // Set up DB connection
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        // By default, hold date is blank
        $shipping_hold_date = ""; 

        $method = $order->getData('shipping_method');

        if ( substr( $method, 0, 19) == 'amstrates_amstrates' )
        {
            // Whatever follows is the ID from amasty_table_method
            $id = substr($method,19);

            if ( $id )
            {
                // Select comment from shipping table
                $sql = "SELECT comment FROM amasty_table_method WHERE id = :id";
                $shipping_hold_date = $connection->fetchOne($sql, [':id' => $id]);
            }

        }

        return $shipping_hold_date;
    }
}  