<?php

namespace BlueEx\Shipping\Api\Data;

/**
 * @api
 */
interface BluexDataInterface extends \Magento\Framework\Api\CustomAttributesDataInterface
{
	/**#@+
	 * Constants defined for keys of the data array. Identical to the name of the getter in snake case
	 */
	const ID = 'id';

	const CUSTOMER_ID = 'customer_id';
	
	const PRODUCT_ID = 'product_id';
	/**#@-*/

	/**
	 * Get Id.
	 *
	 * @return int|null
	 */
	public function getId();

	/**
	 * Set Id.
	 *
	 * @param int $id
	 * @return $this
	 */
	public function setId($id = null);

	/**
	 * Get Customer Id.
	 *
	 * @return int|null
	 */
	public function getCustomerId();

	/**
	 * Set Customer Id.
	 *
	 * @param int $customerId
	 * @return $this
	 */
	public function setCustomerId($customerId = null);
	
	/**
	 * Get Product Id.
	 *
	 * @return int|null
	 */
	public function getProductId();
	
	/**
	 * Set Product Id.
	 *
	 * @param int $productId
	 * @return $this
	 */
	public function setProductId($productId = null);
}