<?php

namespace BlueEx\Shipping\Api;

interface BluexRepositoryInterface
{

	/**
     * Get info about product by product SKU
     *
     * @param string $sku
     * @param bool $editMode
     * @param int|null $storeId
     * @param bool $forceReload
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
	public function getProductRequest($sku, $editMode = false, $storeId = null, $forceReload = false);

	/**
     * Get info about product by product SKU
     *
     * @param int $id
     * @param bool $editMode
     * @param int|null $storeId
     * @param bool $forceReload
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
	public function getProductById($id, $editMode = false, $storeId = null, $forceReload = false);


    /**
     * Get info about order by increment ID
     *
     * @param int $id The order ID.
     * @param int|null $storeId
     * @return \Magento\Sales\Api\Data\OrderInterface Order interface.
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
	public function getOrderRequest($id, $storeId = null);

     /**
     * Get info about order by increment ID
     *
     * @param int $id The order ID.
     * @return \Magento\Sales\Api\Data\OrderInterface Order interface.
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
     public function getFullHistory($id);

	/**
     * update Order Qty
     *
     * @param int $order_id The order ID.
     * @param string $sku .
     * @param int $qty.
     * @return string 
     */
	public function updateOrderItemQty($order_id,$sku,$qty);

     /**
     * update Product Qty
     *
     * @param string $sku .
     * @param int $qty.
     * @return array 
     */
     public function updateProductQty($sku,$qty);

	/**
     * remove Order item
     *
     * @param int $order_id The order ID.
     * @param string $sku .
     * @return string 
     */
	public function removeOrderItem($order_id,$sku);

	/**
     * add Order item
     *
     * @param int $order_id The order ID.
     * @param string $sku .
     * @param int $qty.
     * @return string 
     */
	public function addOrderItem($order_id,$sku,$qty);

     /**
     * Get all children for Configurable product
     *
     * @param string $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getConfChild($sku);

    /**
     * Get all children for Configurable product
     *
     * @param int $id
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getConfChildById($id);


}