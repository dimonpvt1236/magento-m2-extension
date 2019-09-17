<?php

namespace Drip\Connect\Setup;

use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class Uninstall implements UninstallInterface
{
    protected $eavSetupFactory;

    public function __construct(\Magento\Eav\Setup\EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $setup->getConnection()->dropColumn($setup->getTable('quote'), 'drip');

        $eavSetup = $this->eavSetupFactory->create();
        $entityTypeId = 1; // customer
        $eavSetup->removeAttribute($entityTypeId, 'drip');

        $installer->endSetup();
    }
}
