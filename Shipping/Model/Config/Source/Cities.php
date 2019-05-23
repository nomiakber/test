<?php
namespace BlueEx\Shipping\Model\Config\Source;

class Cities implements \Magento\Framework\Option\ArrayInterface
{
 	public function toOptionArray(){
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$cityList = $objectManager->create('BlueEx\Shipping\Helper\Data')->getCityList();
		
		$list = [];
		foreach($cityList as $city){
			$list[] = ['value' => $city['city_code'], 'label' => ucfirst($city['city_name'])];
		}
		
		return $list;
 	}
}