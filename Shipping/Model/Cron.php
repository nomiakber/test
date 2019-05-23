<?php

namespace BlueEx\Shipping\Model;

class Cron
{
	protected $objectManager;
	protected $helperData;
	protected $logger;
	
	protected $urlBuilder;
	protected $connection;
	protected $dbTable;
	protected $orderManagement;
	protected $resource;
	
	// load objects
    public function __construct() {
		   
		$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->helperData = $this->objectManager->create('BlueEx\Shipping\Helper\Data');
	    $this->logger = $this->objectManager->create('Psr\Log\LoggerInterface');
		
		$this->urlBuilder = $this->objectManager->create('Magento\Framework\UrlInterface');
      	
		$this->resource = $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
		$this->connection = $resource->getConnection();
		$this->dbTable = $resource->getTableName('blueex_shipping_data'); 
		$this->orderManagement = $this->objectManager->create('Magento\Sales\Api\OrderManagementInterface');
		
	}

	// call cron function
	public function runCron()
	{
	    
		if ( $this->helperData->isEnabled() ){

			// update orders
			$response = $this->cronUpdateOrders();
			
			// create log for api response.
			$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/cron.log');
			$logger = new \Zend\Log\Logger();
			$logger->addWriter($writer);
			$logger->info("ORDERS ".$response);
		}
		return $this;
	}
	
	public function cronUpdateOrders(){
			
		$rows = $this->connection->fetchAll("SELECT * FROM ".$this->dbTable." ORDER BY id DESC LIMIT 0,500");
		
		if( !empty($order_codes) ){ 
			$cnStatuses = $this->helperData->getCNStatus($order_codes);
		
			$statusList = array_unique(array_values($cnStatuses));
			$exitsStatuses = array_column($this->connection->fetchAll("SELECT status FROM ".$this->resource->getTableName('sales_order_status')),'status');
			$exitsStatuses2 = array_column($this->connection->fetchAll("SELECT status FROM ".$this->resource->getTableName('sales_order_status_state')),'status');
			
			foreach($statusList as $_sts){
				$__sts = trim(str_replace(' ','_',strtolower($_sts)));
				if($__sts == 'cancel' ) $__sts = 'canceled';						

				if( !in_array($__sts,$exitsStatuses) )
					$this->connection->query("INSERT INTO ".$this->resource->getTableName('sales_order_status')." (`status`,`label`) VALUES ('$__sts','$_sts')");

				if( !in_array($__sts,$exitsStatuses2) )
					$this->connection->query("INSERT INTO ".$this->resource->getTableName('sales_order_status_state')." (`status`,`state`,`is_default`,`visible_on_front`) VALUES ('$__sts','$__sts',0,1)");				
			}

			foreach ($rows as $row){
	
				$cn_data = !empty($row['data']) ? json_decode($row['data'],true) : [];
				if( !empty($cn_data['order_code']) ){
					$order_code = (int)@$cn_data['order_code'];
					if( !empty($cnStatuses[$order_code]) ){
						
						$cn_status = trim(str_replace(' ','_',strtolower($cnStatuses[$order_code])));									
						if( $cn_status != 'pending'){
							
							$orderstatus = $cn_status;
							if($cn_status == 'cancel' )
								$orderstatus = 'canceled';
							
							$order = $this->objectManager->create('\Magento\Sales\Model\Order')->load($row['order_id']);
							
							if ($order->getId() ) {
			
								if( strtolower($order->getState()) != $orderstatus ){ 
											
									$order->setState($orderstatus)->setStatus($orderstatus);
									if( $order->save() ){ 
										
										$i++;
										echo "<br>".$order->getId() ." = $orderstatus";
										
										if( 'canceled' != strtolower($order->getState()) ){ 
											$orderItems = $order->getAllItems();
											$itemQtys = array();
											foreach ($orderItems as $item) {
												$itemQtys[]=array('quantity'=>$item->getQtyOrdered(),'id'=>$item->getProductId());	
											}
											
											foreach($itemQtys as $itemQty){
												$product = $this->objectManager->create('\Magento\Catalog\Model\Product')->load($itemQty['id']);
												$stockItem = $this->objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');
						
												$stock = $stockItem->getStockItemBySku($product->getSku());
												$qty = $stock->getQty();
												
												$stock->setQty($qty+$itemQty['quantity']);
												$stockItem->updateStockItemBySku($product->getSku(), $stock);
											}  
										}
											
									}
								}
								
							}
						}
					}
				}
			}
		}
		echo "<br><br>DONE: ".$i;
		exit;
	}
}