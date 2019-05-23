<?php
namespace BlueEx\Shipping\Controller\Api;
use Magento\Framework\App\Action\Context;
 
class Updateorder extends \Magento\Framework\App\Action\Action{
	
	protected $helperData;
	protected $objectManager;
	protected $request;
	
	private $authToken = 'A6g9rs3T5m4lz7W1';
	
	public function __construct(
		\Magento\Backend\App\Action\Context $context,
	   \Magento\Framework\App\Request\Http $request 
	){
		$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$helperData = $this->objectManager->create('BlueEx\Shipping\Helper\Data');
		$this->helperData = $helperData;
		$this->request = $request;
		parent::__construct($context);
	}
	
	public function execute()
	{		
		$orderno = trim($this->request->getParam('orderno'));
		$token 	 = trim($this->request->getParam('token'));
		
		$this->error	 = false;
		$this->success = false;
		
		if( empty($orderno) )
			$this->error = 'Empty Order Number';
			
		else if( empty($token) )
			$this->error = 'Empty Auth token';
		
		else if( @$_SERVER['REQUEST_METHOD'] != 'POST' )
			$this->error = 'Only POST Method allowed';	
			
		else{
			
			if( $this->authrozied($token) ){
		
				$order = $this->objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderno);
				if( $order->getId() )
					$this->updateOrder($order);	
						
				else 
					$this->error = 'Order does not exists';
				
			} else 
				$this->error = 'Invalid Auth token';
		}
		
		header('Content-type: application/json');
		echo json_encode($this->error ? ['error' => $this->error] : ['success' => $this->success]);
		exit;	
	}
	
	private function updateOrder($order){
		
		$data = $this->getPostData();
		
		$data_b = @$data['billing_address'];
		$data_s = @$data['shipping_address'];
		
		if( !empty($data_b) && !empty($data_s) ){
				
			try{ 
				
				$ba = $order->getBillingAddress();
				$ba->setFirstname(@$data_b['firstname']);
				$ba->setLastname(@$data_b['lastname']);
				$ba->setEmail(@$data_b['email']);
				$ba->setTelephone(@$data_b['phone']);
				$ba->setStreet(@$data_b['address']);
				$ba->setCity(@$data_b['city']);
				$ba->setRegion(@$data_b['state']);
				$ba->setCountryId(@$data_b['country_code']);
				$ba->setPostcode(@$data_b['postcode']);
					
				$sa = $order->getShippingAddress();
				$sa->setFirstname(@$data_s['firstname']);
				$sa->setLastname(@$data_s['lastname']);
				$sa->setEmail(@$data_s['email']);
				$sa->setTelephone(@$data_s['phone']);
				$sa->setStreet(@$data_s['address']);
				$sa->setCity(@$data_s['city']);
				$sa->setRegion(@$data_s['state']);
				$sa->setCountryId(@$data_s['country_code']);
				$sa->setPostcode(@$data_s['postcode']);
				
				$order->setBillingAddress($ba);
				$order->setShippingAddress($sa);
				
				if($order->save() )
					$this->success = true;
				else 
					$this->error = "Unable to update customer information";
			
			} catch (Exception $e){
				$this->error = $e->getMessage();
			}
		} else {
				$this->error = "Invalid request. missing billing/shipping address";
		}
	}
	
	
	///////////////////////////////////////////////////////
	
	private function getPostData(){
		
		$content = file_get_contents('php://input');
		if( empty($content) )
			$data = $_POST;
		else
			$data = json_decode($content,true);
		/*
		$data = [
			'billing_address' => [
				'firstname' => 'API',
				'lastname' => 'Update',
				'email' => 'test3111111111112@gmail.com',
				'phone' => '+9865327410',
				'address' => '#5666, near abc',
				'country_code' => 'IN',
				'city' => 'Mohali',
				'state' => 'Punjab',
				'postcode' => '160071',
			],
			'shipping_address' => [
				'firstname' => 'Abc',
				'lastname' => 'User',
				'email' => 'test1@gmail.com',
				'phone' => '+000000000',
				'address' => '#98-2',
				'country_code' => 'IN',
				'city' => 'Chandigarh',
				'state' => 'Chandigarh',
				'postcode' => '160043',
			],
		];
		echo json_encode($data );
		*/
		return $data;
	}	
	
	public function authrozied($token){
		
		if( $token == $this->authToken )
			return true;	
		
		return false;
	}
	
}