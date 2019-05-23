<?php
 
namespace BlueEx\Shipping\Setup;
 
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
 
class InstallSchema implements InstallSchemaInterface
{
	public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
	{
		$installer = $setup;
		$installer->startSetup();
 
		$tableName = $installer->getTable('blueex_shipping_data');
		if ($installer->getConnection()->isTableExists($tableName) != true) {

			$table = $installer->getConnection()
				->newTable($tableName)
				->addColumn(
					'id',
					Table::TYPE_INTEGER,
					null,
					[
						'identity' => true,
						'unsigned' => true,
						'nullable' => false,
						'primary' => true
					],
					'ID'
				)
				->addColumn(
					'order_id',
					Table::TYPE_INTEGER,
					null,
					['nullable' => false, 'default' => '0'],
					'Order ID'
				)
				->addColumn(
					'cn_id',
					Table::TYPE_TEXT,
					null,
					['nullable' => false, 'default' => ''],
					'CN ID'
				)
				->addColumn(
					'logistic_type',
					Table::TYPE_TEXT,
					null,
					['nullable' => true, 'default' => ''],
					'Logistic Type'
				)
				->addColumn(
					'data',
					Table::TYPE_TEXT,
					null,
					['nullable' => false, 'default' => ''],
					'Data'
				)
				->addColumn(
					'datetime',
					Table::TYPE_DATETIME,
					null,
					['nullable' => false],
					'Created At'
				)
				->setComment('BlueEx Shipping Data')
				->setOption('type', 'InnoDB')
				->setOption('charset', 'utf8');
			$installer->getConnection()->createTable($table);
		}
 
		$installer->endSetup();
	}
}