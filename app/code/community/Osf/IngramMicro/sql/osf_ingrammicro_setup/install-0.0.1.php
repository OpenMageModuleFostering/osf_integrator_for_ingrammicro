<?php 

$installer = $this;
$installer->startSetup();

//Create queue table to store ingrammicro failed request for retry.
$installer->getConnection()->dropTable($installer->getTable('osf_ingrammicro_queue'));
$table = $installer->getConnection()
	->newTable($installer->getTable('osf_ingrammicro_queue'))
	->addColumn('queue_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
			'identity'  => true,
			'unsigned'  => true,
			'nullable'  => false,
			'primary'   => true,
		)
	)
	->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
			'nullable' => false
		)
	)
	->addColumn('po_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11)
	->addColumn('order_xml', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
			'nullable' => false
		)
	)
	->addColumn('retry', Varien_Db_Ddl_Table::TYPE_INTEGER, 2)
	->addColumn('prev_error', Varien_Db_Ddl_Table::TYPE_TEXT);

$installer->getConnection()->createTable($table);

//Create a table to store category mapping
$installer->getConnection()->dropTable($installer->getTable('osf_ingrammicro_catmap'));
$table = $installer->getConnection()
    ->newTable($installer->getTable('osf_ingrammicro_catmap'))
    ->addColumn('catmap_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
	    	'identity'  => true,
	        'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        )
    )
    ->addColumn('ingrammicro_category', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
    		'nullable' => false
    	)
    )
    ->addColumn('magento_category', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
    		'nullable' => false
    	)
    );
$installer->getConnection()->createTable($table);

//Add new product attributes
$attr_installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');

//Create new Vendor attribute
$attr_installer->addAttribute('catalog_product', 'osf_product_vendor', array(
	'type'              => 'varchar',
	'backend'           => '',
	'frontend'          => '',
	'label'             => 'Vendor',
	'input'             => 'text',
	'class'             => '',
	'source'            => 'catalog/product_attribute_source_layout',
	'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'visible'           => true,
	'required'          => false,
	'user_defined'      => false,
	'default'           => '',
	'searchable'        => false,
	'filterable'        => false,
	'comparable'        => false,
	'visible_on_front'  => false,
	'unique'            => false,
	'group'             => 'General'
));

//Create new Cost attribute
$attr_installer->addAttribute('catalog_product', 'osf_product_cost', array(
	'type'              => 'varchar',
	'backend'           => '',
	'frontend'          => '',
	'label'             => 'Cost',
	'input'             => 'text',
	'class'             => '',
	'source'            => 'catalog/product_attribute_source_layout',
	'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'visible'           => true,
	'required'          => false,
	'user_defined'      => false,
	'default'           => '',
	'searchable'        => false,
	'filterable'        => false,
	'comparable'        => false,
	'visible_on_front'  => false,
	'unique'            => false,
	'group'             => 'General'
));

$installer->endSetup();