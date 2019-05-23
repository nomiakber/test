<?php
namespace BlueEx\Shipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use BlueEx\Shipping\Helper\Data;

class OrderSuccess implements ObserverInterface
{
	protected $_productloader; 
	protected $scopeConfig;
	  
	const XML_PATH_EMAIL_RECIPIENT = 'blueex_shipping/general/enable_dtb';

	public function __construct(Data $helperData,
		\Magento\Customer\Model\Session $customerSession, 
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
		\Magento\Catalog\Model\ProductFactory $_productloader,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
		)
	{
		$this->helperData = $helperData;
        $this->customerSession = $customerSession;
		$this->_productloader = $_productloader;
		$this->_customerRepositoryInterface = $customerRepositoryInterface;
		$this->scopeConfig = $scopeConfig;
		$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->urlBuilder = $this->objectManager->create('Magento\Framework\UrlInterface');

	}	
	
    public function execute(\Magento\Framework\Event\Observer $observer){
		
		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
	
		$enable_dtb = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, $storeScope);
		if(!empty($enable_dtb)){
			
			$orderIds = $observer->getData('order_ids');
			$orderId = $orderIds[0];
			$order = $this->objectManager->create('Magento\Sales\Model\Order')->load($orderId);
			$shippingAddressObj = $order->getShippingAddress();

			$shippingAddressArray = $shippingAddressObj->getData();
			$city = $shippingAddressArray['city']; 

			$order_id = (int)$orderId;
			$service_type = trim('BE');
			$total_amount = (float)@$order->getGrandTotal();
			$_city = $this->getCitycode($city);
			$city = $_city ? $_city : $city;
			$charges = (float)@$order->getShippingAmount();
			$cod_amount = 0.00;
			$insurance = false;
			$collect_cash = false;
			
			$success = $error = $response = false;
			$cn_id = '';
			$cn_text = '';
			$oms = '';
			$checkMethod = 'no';
			
			if( !empty($order_id) ){

				list($cn_id,$error,$response,$enable_oms,$response) = $this->getApiCN($order_id,$service_type,$charges,$insurance,$city,$cod_amount,$collect_cash);
				
				$checkMethodGet = $this->checkMethod();
				if( $error && $checkMethodGet == 'no'){
					list($cn_id,$error,$response,$enable_oms,$response) = $this->getApiCN($order_id,$service_type,$charges,$insurance,$city,$cod_amount,$collect_cash);			
				}
				
				$oms = $enable_oms;
				if( !$error ){
					
					$data =  [
						//'service_type'=>$service_type, 
						'total_amount'=>$total_amount, 
						'charges'=>$charges, 
						'city'=>$city,
						'cod_amount'=>$cod_amount,
						'collect_cash'=> $collect_cash ? "Y" : "N" ,
						'insurance'=> $insurance  ? "Y" : "N", 
						'order_code'=> (int)@$response['result']['order_code'],
						'cn_number'=> $enable_oms ? "" : $cn_id,
						'oms' => $enable_oms ? 'Y' : 'N',
					];
					
					$success = true;
					$this->updateCNNumber($order_id,$cn_id,$data);
				}
						
			} else
				$error = 'Invalid Request';
			
		}
	}

	public function checkMethod(){
		return 'yes';
	}
	
	private function getApiCN($order_id,$service_type,$charges,$insurance,$city,$cod_amount,$collect_cash){
		
		$error= $response = false;
		$cn_id = false;
		try{ 
			$order = $this->objectManager->create('Magento\Sales\Model\Order')->load($order_id);
			$m_order_code = $order->getIncrementId(); 
			
			$auth = '<api_code>'. $this->helperData->getGeneralConfig('api_key').'</api_code>
				<acno>'. $this->helperData->getGeneralConfig('accout_number').'</acno>
				<testbit>'. ( (int)$this->helperData->getGeneralConfig('live_mode') ? 'n' : 'y').'</testbit>
				<userid>'. $this->helperData->getGeneralConfig('customer_name').'</userid>
				<password>'.$this->helperData->getGeneralConfig('customer_password').'</password>';
				
			$OriginCity = $this->helperData->getGeneralConfig('origin_citycode');
			$insurancePrice = 0;
			$orderStatus = "B";
			
			$paymentType = $order->getPayment()->getMethodInstance()->getCode();
	
			$billingAddress = $order->getBillingAddress();
			$shippingAddress = $order->getShippingAddress();

			$orderId = $order->getId();
			$orderIncrementId = $order->getIncrementId();
			$giftCharges = 0;
			if($order->getData('base_osc_gift_wrap_amount')){
				$giftCharges = $order->getData('base_osc_gift_wrap_amount');
			}
	
			$billingCity = $billingAddress->getCity();
			$shippingCity = $shippingAddress->getCity();
			
			$billingCity = $shippingCity = trim($city);
			
			$addressInfo = '<shipper_name>'.$shippingAddress->getName().'</shipper_name>
				<shipper_email>'.$shippingAddress->getEmail().'</shipper_email>
				<shipper_contact>'.$shippingAddress->getTelephone().'</shipper_contact>
				<shipper_address>'.str_replace(', ,',',',str_replace(["\n","\r","\r\n","\n\r"],", ",$shippingAddress['street'])).'</shipper_address>
				<shipper_city>'.$billingCity.'</shipper_city>
				<shipper_country>'.$billingAddress->getCountryId().'</shipper_country>
				<billing_name>'.$billingAddress->getName().'</billing_name>
				<bill_email>'.$billingAddress->getEmail().'</bill_email>
				<billing_contact>'.$billingAddress->getTelephone().'</billing_contact>
				<billing_address>'.str_replace(', ,',',',str_replace(["\n","\r","\r\n","\n\r"],", ",$billingAddress['street'])).'</billing_address>
				<billing_city>'.$shippingCity.'</billing_city>
				<billing_country>'.$billingAddress->getCountryId().'</billing_country>';
			
			$products = [];
			$items = $order->getAllItems();
			foreach($items as $item):
				
				if($item->getProductType() == 'configurable'){ 
				
					$productObject = $this->objectManager->get('Magento\Catalog\Model\Product');
                    $productConf = $productObject->load($item->getProductId());
                   
                    $parentSku = $productConf->getSku();
                }else{
                    $parentSku = 'None';
                }

                	$products[] = '<products_detail>
					  <product_type>'.$item->getProductType().'</product_type>
					  <parent_sku>'.$parentSku.'</parent_sku>
					  <product_code>'.$item->getSku().'</product_code>
					  <product_id>'.$item->getProductId().'</product_id>
					  <product_name>'. $item->getName().'</product_name>
					  <product_price>'. number_format($item->getPrice(),2,'.','').'</product_price>
					  <product_quantity>'.$item->getQtyOrdered().'</product_quantity>
					</products_detail>';
					
			endforeach;
		
			$cn_generate = 'y';
			$oms = '';
			$enable_oms = (int)@$this->helperData->getGeneralConfig('enable_oms');
			
			if( $enable_oms ){		
				$cn_generate = 'n';
				$oms = '<oms>1</oms>';	
			}
			$xml = '<?xml version="1.0" encoding="utf-8"?>
			<BenefitDocument>
			  <AccessRequest>
				<DocumentType>1</DocumentType>
				<Orderdetail>
				  '.$auth.'
				  <cn_generate>'.$cn_generate.'</cn_generate>
				  '.$oms .$addressInfo.'
				  <order_status>'.$orderStatus.'</order_status>
				  <order_id>'.$orderId.'</order_id>
				  <order_increment_id>'.$orderIncrementId.'</order_increment_id>
				  <gift_charges>'.$giftCharges.'</gift_charges>
				  <credit_card>NC</credit_card>
				  <customer_comment></customer_comment>
				  <staff_comment></staff_comment>
				  <shipping_charges>'.$charges.'</shipping_charges>
				  <payment_type>'.$paymentType.'</payment_type>
				  <current_currency>PKR</current_currency>
				  <currency_code>3</currency_code>
				  <all_products>
				  '.implode("",$products).'
				  </all_products>
				  <OriginCity>'.$OriginCity.'</OriginCity>
				  <ServiceCode>'.$service_type.'</ServiceCode>
				  <ParcelType>P</ParcelType>
				  <Fragile>1</Fragile>
				  <InsuranceRequire>'.($insurance ? 'Y' : 'N').'</InsuranceRequire>
				  <InsuranceValue>'.$insurancePrice.'</InsuranceValue>
				  <ShipperComment></ShipperComment>
				  <codamount>'.$cod_amount.'</codamount>
				  <cashcollect>'.($collect_cash ? 'y' : 'n').'</cashcollect>
				  <magento_order_code>'.$m_order_code.'</magento_order_code>
				</Orderdetail>
			  </AccessRequest>
			</BenefitDocument>';
			  
			$response = $this->helperData->callHttp('order/order_api',$xml);
			if( @$response['error']	)
				$error = $response['error'];
			else {
				if( $enable_oms ){
					if( !empty($response['result']['oms']) )
						$cn_id = 'Order Sent To BlueEx';
					else
						$error = 'Unabel to send Order to BlueEx, try again';					
				}
				else {
					if( empty($response['result']['order_code']) )
						$error = 'Unabel to generate CN number, try again';
				}
			}

			$cn_id = !empty($response['result']['cn']) ? trim($response['result']['cn']) : '';	
			
		} catch(Exception $e){
			$error= $this->getMessage();	
		}

		return [$cn_id,$error,@$response['result'],$enable_oms,$response];
	}
	
	public function getCitycode($city){
		$city_code = false;
		$cities = $this->helperData->getCityList();
		foreach($cities as $key => $val){
			if( strtolower($val['city_name']) == strtolower(trim($city)))	{
				$city_code = strtoupper($val['city_code']);
			}
		}
		return $city_code;
	}
		private function updateCNNumber($order_id,$cn_id,$data){
		
		$resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection = $resource->getConnection();
		$tableName = $resource->getTableName('blueex_shipping_data'); 

		$data = json_encode($data);	

		$check = $connection->fetchAll("SELECT id FROM $tableName WHERE order_id = $order_id");
		if( empty($check) )
			$sql = "Insert Into " . $tableName . " (order_id, cn_id, `data`, `datetime`) Values ($order_id,'$cn_id','$data','".date("Y-m-d H:i:s")."')";
		else 
			$sql = "UPDATE $tableName SET cn_id = '$cn_id',`data` = '$data', `datetime` = `datetime` WHERE order_id = $order_id";	
		
		$connection->query($sql);
		$check2 = $connection->fetchAll("SELECT id FROM $tableName WHERE order_id = $order_id");
				
	}
}