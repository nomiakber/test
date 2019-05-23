<?php
namespace BlueEx\Shipping\Controller\Api;
use Magento\Framework\App\Action\Context;
 
class Tariff extends \Magento\Framework\App\Action\Action{
	
	protected $helperData;
	protected $objectManager;
	
	public function __construct(
		\Magento\Backend\App\Action\Context $context
	){
		$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$helperData = $this->objectManager->create('BlueEx\Shipping\Helper\Data');
		$this->helperData = $helperData;
		parent::__construct($context);
	}
	
	public function execute()
	{
		$originCity = $this->helperData->getGeneralConfig('origin_citycode');	
		$destinationCountry	 = 'PK';
		
		$serviceCode = trim(@$_REQUEST['service_code']);
		$distyCity = trim(@$_REQUEST['city']);
		$weight = floatval(@$_REQUEST['weight']);
		$cbc_amount = floatval(@$_REQUEST['cbc_amount']);
		
		$xml = '<?xml version="1.0" encoding="utf-8"?>
		<BenefitDocument>
		  <AccessRequest>
			<DocumentType>5</DocumentType>
			<ShipmentDetail>
			  <ServiceCode>'.$serviceCode.'</ServiceCode>
			  <OriginCity>'.$originCity.'</OriginCity>
			  <DestinationCountry>'.$destinationCountry.'</DestinationCountry>
			  <DestinationCity>'.$distyCity.'</DestinationCity>
			  <Weight>'.$weight.'</Weight>
			  <CBCAmount>'.$cbc_amount.'</CBCAmount>
			  <ParcelType>P</ParcelType>
			</ShipmentDetail>
		  </AccessRequest>
		</BenefitDocument>';
	
		$response = $this->helperData->callHttp('tariff/get_tariff',$xml);
		
		header("Content-type: application/json");
		echo json_encode($response);
		exit;
	}	
	
}