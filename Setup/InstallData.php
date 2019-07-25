<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Sitemap
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Sitemap\Setup;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class InstallData
 * @package Mageplaza\Sitemap\Setup
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var CategorySetupFactory
     */
    protected $categorySetupFactory;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * InstallData constructor.
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->categorySetupFactory = $categorySetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        /**
         * Product attribute
         */
        $eavSetup->removeAttribute(Product::ENTITY, 'mp_exclude_sitemap');
        $eavSetup->addAttribute(Product::ENTITY, 'mp_exclude_sitemap', [
            'type'                    => 'varchar',
            'backend'                 => '',
            'frontend'                => '',
            'label'                   => 'Exclude Sitemap',
            'note'                    => 'Added by Mageplaza Sitemap',
            'input'                   => 'select',
            'class'                   => '',
            'source'                  => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible'                 => true,
            'required'                => false,
            'user_defined'            => false,
            'default'                 => '',
            'searchable'              => false,
            'filterable'              => false,
            'comparable'              => false,
            'visible_on_front'        => false,
            'used_in_product_listing' => true,
            'unique'                  => false,
            'group'                   => 'Search Engine Optimization',
            'sort_order'              => 100,
            'apply_to'                => '',
        ]);

        /**
         * Category attribute
         */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);

        $categorySetup->removeAttribute(Category::ENTITY, 'mp_exclude_sitemap');
        $categorySetup->addAttribute(Category::ENTITY, 'mp_exclude_sitemap', [
            'type'       => 'int',
            'label'      => '',
            'input'      => 'select',
            'source'     => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'required'   => false,
            'sort_order' => 100,
            'global'     => ScopedAttributeInterface::SCOPE_STORE,
            'group'      => 'Search Engine Optimization',
        ]);

        $setup->endSetup();
    }
}
