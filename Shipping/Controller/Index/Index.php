<?php
/**
 * Copyright 2018 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace BlueEx\Shipping\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\View\Result\Page;


class Index extends Action
{

     public function execute()
    {
    	    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$orderId = 55080;
			$order = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);
			$order->cancel();
            $order->save();
            echo 'cancelled';exit;

			//echo '<pre>'; print_r($order->getData());exit;
			$shippingAddressObj = $order->getShippingAddress();

			$shippingAddressArray = $shippingAddressObj->getData();
			$city = $shippingAddressArray['city']; 

			$order_id = (int)$orderId;
			$total_amount = (float)@$order->getGrandTotal();
			$charges = (float)@$order->getShippingAmount();
			$cod_amount = 0.00;
			$insurance = false;
			$collect_cash = false;
			
			$success = $error = $response = false;
			$cn_id = '';
			$cn_text = '';
			$oms = '';


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
			$parentsku = 
			$items = $order->getAllItems();
			foreach($items as $item):

				if($item->getProductType() == 'configurable'){ 
				
					$productObject = $objectManager->get('Magento\Catalog\Model\Product');
                    $productConf = $productObject->load($item->getProductId());
                   
                    $parentSku = $productConf->getSku();
                }else{
                    $parentSku = 'None';
                }

                	$products[] = '<products_detail>
					  <product_type>'.$item->getProductType().'</product_type>
					  <parent_sku>'.$parentSku.'</parent_sku>
					  <product_code>'.$item->getSku().'</product_code>
					  <product_name>'. $item->getName().'</product_name>
					  <product_price>'. number_format($item->getPrice(),2,'.','').'</product_price>
					  <product_quantity>'.$item->getQtyOrdered().'</product_quantity>
					</products_detail>';

				if($item->getProductType() == 'configurable'){ 
				
					$productObject = $objectManager->get('Magento\Catalog\Model\Product');
                    $productConf = $productObject->load($item->getProductId());
                   
                    $parentSku = '<parent_sku>'.$productConf->getSku().'</parent_sku>';
                }else{
                   $parentSku = '<parent_sku>None</parent_sku>';
                }

                if($item->getPrice() > 0 ){
                	$products[] = '<products_detail>
					  <product_type>'.$item->getProductType().'</product_type>'
                      .$parentSku.'
					  <product_code>'.$item->getSku().'</product_code>
					  <product_id>'.$item->getProductId().'</product_id>
					  <product_name>'. $item->getName().'</product_name>
					  <product_price>'. number_format($item->getPrice(),2,'.','').'</product_price>
					  <product_quantity>'.$item->getQtyOrdered().'</product_quantity>
					</products_detail>';
                }
				
			endforeach;

			$xml = '<?xml version="1.0" encoding="utf-8"?>
			<BenefitDocument>
			  <AccessRequest>
				<DocumentType>1</DocumentType>
				<Orderdetail>
				  '.$addressInfo.'
				  <credit_card>NC</credit_card>
				  <customer_comment></customer_comment>
				  <order_id>'.$orderId.'</order_id>
				  <order_increment_id>'.$orderIncrementId.'</order_increment_id>
				  <gift_charges>'.$giftCharges.'</gift_charges>
				  <staff_comment></staff_comment>
				  <current_currency>PKR</current_currency>
				  <currency_code>3</currency_code>
				  <all_products>
				  '.implode("",$products).'
				  </all_products>
				</Orderdetail>
			  </AccessRequest>
			</BenefitDocument>';

            echo '<pre>'; echo htmlentities($xml);exit;

			echo '<pre>'; print_r($xml);exit;
    }
}