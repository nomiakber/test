<?php 

namespace BlueEx\Shipping\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;

class Blueex extends Column
{
	protected $urlBuilder;
    protected $_orderRepository;
    protected $_searchCriteria;
	protected $isEnabled = false;
	protected $connection;
	protected $helperData;
	protected $dbTable;
	protected $cityList;
	protected $objectManager;

    public function __construct(ContextInterface $context, UiComponentFactory $uiComponentFactory, OrderRepositoryInterface $orderRepository, SearchCriteriaBuilder $criteria,UrlInterface $urlBuilder, array $components = [], array $data = [])
    {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria  = $criteria;
		$this->urlBuilder = $urlBuilder;
      	$this->isEnabled = true;
		
		$this->objectManager = $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->helperData = $helperData = $objectManager->create('BlueEx\Shipping\Helper\Data');
		
		$this->isEnabled = (bool)@$helperData->getGeneralConfig('enable') && !empty($helperData->getGeneralConfig('api_key'));			
		$this->cityList = $this->getCityList();
		
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$this->connection = $resource->getConnection();
		$this->dbTable = $resource->getTableName('blueex_shipping_data'); 
		
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

	public function getCityList(){
		
		$cityList = $this->objectManager->create('BlueEx\Shipping\Helper\Data')->getCityList();
		
		$list = ['<select class="bootbox-input bootbox-input-text form-control required-entry" style=" width:150px; border: 1px solid #ccc;padding: 3px 5px;" name="city">'];
		
		foreach($cityList as $city){
			$list[] = '<option value="'.$city['city_code'].'">'.ucfirst($city['city_name']).'</option>';
		}
		
		$list[] = '</select>';
		return implode('',$list);	
	}
	
    public function prepareDataSource(array $dataSource)
    {

        if (isset($dataSource['data']['items'])) {
		if( $this->isEnabled ){ 
			$fieldName = $this->getData('name');
			$order_codes = [];
				foreach ($dataSource['data']['items'] as & $item){
   
					 $order_id = (int)$item['entity_id'];
			
					$row = $this->connection->fetchAll("SELECT cn_id,logistic_type,`data` FROM ".$this->dbTable." WHERE order_id = $order_id");
					
					$cn_data = !empty($row[0]['data']) ? json_decode($row[0]['data'],true) : [];
					
					if( !empty($cn_data['order_code']) )
						$order_codes[] = '<order_code>'.$cn_data['order_code'].'</order_code>';
					
					$item['_row'] = $row;
				}
				
				$cnStatuses = $this->helperData->getCNStatus($order_codes);
				foreach ($dataSource['data']['items'] as & $item){
					
					
					$row = $item['_row']; unset($item['_row']);
					 $order_id = (int)$item['entity_id'];
					$ordLen = strlen($order_id);
					$ostr_id = ''; for($i=1;$i<= 8-$ordLen; $i++ ){$ostr_id .='0';	} $ostr_id .=	$order_id;
					
					$cn_id = $text_cn = !empty($row[0]['cn_id']) ? $row[0]['cn_id'] : '';
					
					$logistic_type = !empty($row[0]['logistic_type']) ? $row[0]['logistic_type'] : '';
					
					$cn_data = !empty($row[0]['data']) ? json_decode($row[0]['data'],true) : [];
					
					$order_code = (int)@$cn_data['order_code'];
					
					$cn_status = '';
					if( !empty($cnStatuses[$order_code]) ){
						$cn_status ='<br><b>Status: '.$cnStatuses[$order_code].'</b>';
					}
						
				//	$btncn = '<button class="button btn-blueex" id="blueexshippingBtn_'.$order_id.'"><span>BlueEx</span></button>';
				
				    $btncn = '<button class="button btn-blueex" id="btn-submit-blueexshipping" data-id="'.$order_id.'" data-actionurl="'.$this->urlBuilder->getBaseUrl().'blueexshipping/api/cnnumber'.'"><span>BlueEx</span></button>';
				    
				    $btnhtml = '<div style="font-weight:bold; margin-top:5px;font-size: 12px;width: 105px;" id="blueexshippinggencn_'.$order_id.'">'.$btncn.'</div>';
					
					if( @$cn_data['oms'] != 'Y' && !empty($text_cn) ){
						$btn = '<button class="button btn-bevoid" id="blueexshippingBtn_'.$order_id.'" data-actionurl="'.$this->urlBuilder->getBaseUrl().'blueexshipping/api/bluevoid'.'" data-id="'.$order_id.'" data-cnnumber="'.$cn_id.'"><span>VOID</span></button>';
						$text_cn = 'CN&nbsp;#'.$text_cn.@$cn_status.'<div style="margin-top:3px;">
							<a href="http://benefit.blue-ex.com/customerportal/inc/cnprnb.php?'.$cn_id.'">Print&nbsp;CN</a>&nbsp;&nbsp;&nbsp;<a href="http://bigazure.com/api/extensions/magento_tracking.php?trackno='.$cn_id.'">Tracking</a>
						</div>';				
					}else{
	
					}
					
					
					if(!empty($cn_id)){
					    $print = "";
					    $tracking = "";
					    if($logistic_type == 'blueex'){
					        $print = "http://benefit.blue-ex.com/customerportal/inc/cnprnb.php?".$cn_id;
					        $tracking = "http://bigazure.com/api/extensions/magento_tracking.php?trackno=".$cn_id;
					    }
					    
					    if($logistic_type == 'mnp'){
					        $print = "http://bigazure.com/api/extensions/magento_printmnp.php?trackno=".$cn_id;
					        $tracking = "http://bigazure.com/api/extensions/magento_trackingmnp.php?trackno=".$cn_id;
					    }
					    
					    if($logistic_type == 'cc'){
					        $print = "http://cod.callcourier.com.pk/Booking/AfterSavePublic/".$cn_id;
					        $tracking = "http://bigazure.com/api/extensions/magento_trackingcc.php?trackno=".$cn_id;
					    }
    					$btn = '<button class="button btn-bevoid" id="blueexshippingBtn_'.$order_id.'" data-actionurl="'.$this->urlBuilder->getBaseUrl().'blueexshipping/api/bluevoid'.'" data-logistic="'.$logistic_type.'" data-id="'.$order_id.'" data-cnnumber="'.$cn_id.'"><span>VOID</span></button>';
    					$text_cn = ucfirst($logistic_type).@$cn_status.'<div style="margin-top:3px;">
    						<a href="'.$print.'">Print&nbsp;CN</a>&nbsp;&nbsp;&nbsp;<a href="'.$tracking.'">Tracking</a>
    					</div>';
    					
    					$btn = '<span class="button btn-bevoid" id="blueexshippingBtn_'.$order_id.'" data-actionurl="'.$this->urlBuilder->getBaseUrl().'blueexshipping/api/bluevoid'.'" data-id="'.$order_id.'" data-cnnumber="'.$cn_id.'"><span style="color: #007bdb;cursor: pointer;">VOID</span></span>';
    					$text_cn = ucfirst($logistic_type).' | <a href="'.$print.'">Print CN</a> </br> <a href="'.$tracking.'">Tracking</a> | '.$btn;
    					$btnhtml = '<div style="font-weight:bold; margin-top:5px;font-size: 12px;width: 105px;" id="blueexshippinggencn_'.$order_id.'">'.$text_cn.'</div>';
    					
    					//$btnhtml = '<button class="button btn-bevoid" id="blueexshippingBtn_'.$order_id.'" data-actionurl="'.$this->urlBuilder->getBaseUrl().'blueexshipping/api/bluevoid'.'" data-id="'.$order_id.'" data-cnnumber="'.$cn_id.'"><span>VOID</span></button>';
					}
						
					
					
				// 	if(@$cn_data['oms'] == 'Y'){
				// 		$statsusdata = $cn_status;
				// 	}else{
				// 		$statsusdata = '';
				// 	}
				// 	if( @$cn_data['oms'] == 'Y' && !empty($text_cn) )
					//	$btn = '';
						
					$item[$fieldName] = $order_id;
					
					$item[$fieldName . '_cities'] = $this->cityList; 
				//	$item[$fieldName . '_html'] = $btn.'<div style="font-weight:bold; margin-top:5px;" id="blueexshippingCN_'.$order_id.'">'.$text_cn.'</div>'.$statsusdata;
				    $item[$fieldName . '_html'] = $btnhtml;
					$item[$fieldName . '_title'] = __('Order #'.$ostr_id);
					$item[$fieldName . '_submitlabel'] = __('Submit');
					$item[$fieldName . '_orderid'] = $order_id;
				//	$item[$fieldName . '_totalamount'] = number_format($item['grand_total'],2,'.','');
				    $item[$fieldName . '_totalamount'] = $item['grand_total'];
					$item[$fieldName . '_formaction'] = $this->urlBuilder->getBaseUrl().'blueexshipping/api/cnnumber/id/'.$order_id;	

				}	
				
			} //else
				//$item[$fieldName . '_html'] =  'Disabled';	
		}
        return $dataSource;
    }
	
}