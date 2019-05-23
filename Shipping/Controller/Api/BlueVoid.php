<?php
namespace BlueEx\Shipping\Controller\Api;
use Magento\Framework\App\Action\Context;
 
class BlueVoid extends \Magento\Framework\App\Action\Action{
	
	protected $helperData;
	protected $objectManager;
	protected $connection;
	
	public function __construct(
		\Magento\Backend\App\Action\Context $context
	){
	//	$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	    $this->objectManager = $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$helperData = $this->objectManager->create('BlueEx\Shipping\Helper\Data');
		$this->helperData = $helperData;
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$this->connection = $resource->getConnection();
		$this->dbTable = $resource->getTableName('blueex_shipping_data');
		parent::__construct($context);
	}
	
	public function execute()
	{	
		$cn_number = trim(@$_REQUEST['cn_number']);
		$order_id = intval(@$_REQUEST['order_id']);
		
		
		$row = $this->connection->fetchAll("SELECT cn_id,logistic_type,`data` FROM ".$this->dbTable." WHERE order_id = $order_id");
		$logistic_type = $row[0]['logistic_type'];
		$xml = '<?xml version="1.0" encoding="utf-8"?>
		<BenefitDocument>
		  <AccessRequest>
			<DocumentType>20</DocumentType>
		 <ShipmentDetail>
		  <cnNumber>'.$cn_number.'</cnNumber>
		  <logistic_type>'.$logistic_type.'</logistic_type>
		 </ShipmentDetail>
		  </AccessRequest>
		</BenefitDocument>';
	
		$response = $this->helperData->callHttp('void/get_void',$xml);
		
		if( !empty($response['result']['voidStatus']) ){
			$resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
			$connection = $resource->getConnection();
			$tableName = $resource->getTableName('blueex_shipping_data'); 
			$connection->query("DELETE FROM $tableName WHERE order_id = $order_id");
			$order = $this->objectManager->create('Magento\Sales\Model\Order')->load($order_id);
            $error = 'Invalid Request';
            $state = "canceled";
            $status = 'canceled';
            $comment = '';
            $isNotified = false;
            $order->setState($state);
            $order->setStatus($status);
            $order->addStatusToHistory($order->getStatus(), $comment);
            $order->save();

            if ($order->canCancel()) {
                try {
                    $order->cancel();
                    // remove status history set in _setState
                    $order->getStatusHistoryCollection(true);
                    $order->save();
                    // do some more stuff here
                    // ...
                } catch (Exception $e) {
                 //   Mage::logException($e);
                }
            }
		}
		
		header("Content-type: application/json");
		echo json_encode($response);
		exit;
	}	
	
}