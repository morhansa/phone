<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Setup
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Setup;

use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class Uninstall implements UninstallInterface
{
    /**
     * Uninstall module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        // Drop the log table
        if ($setup->tableExists('phonemail_generation_log')) {
            $setup->getConnection()->dropTable($setup->getTable('phonemail_generation_log'));
        }
        
        // Remove config data
        $configTable = $setup->getTable('core_config_data');
        $setup->getConnection()->delete($configTable, "`path` LIKE 'phonemail/%'");
        
        $setup->endSetup();
    }
}