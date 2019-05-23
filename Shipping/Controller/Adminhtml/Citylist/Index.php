<?php
 
namespace BlueEx\Shipping\Controller\Adminhtml\Citylist;
 
class Index extends \Magento\Backend\App\Action
{
	const ADMIN_RESOURCE = 'BlueEx_Shipping::citylist';
	
	protected $resultPageFactory = false;
	protected $helperData;
	
	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	)
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->helperData = $objectManager->create('BlueEx\Shipping\Helper\Data');
		$this->resultPageFactory = $resultPageFactory;
		parent::__construct($context);
	}

	public function execute()
	{
		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend((__('City List')));
		
		$citiesList = $this->helperData->getCityList();

	 	$resultPage->getLayout()->getBlock('Shipping_citylist')->setCities($citiesList);
		
		return $resultPage;
	}
	
}