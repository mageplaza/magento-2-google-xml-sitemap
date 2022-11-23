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
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
declare(strict_types=1);

namespace Mageplaza\Sitemap\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Mageplaza\Sitemap\Model\Source\Boolean as MpBoolean;

/**
* Patch is mechanism, that allows to do atomic upgrade data changes
*/
class CreateAttribute implements
    DataPatchInterface,
    PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var CategorySetupFactory
     */
    protected $categorySetupFactory;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * CreateAttribute constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory,
        EavSetupFactory $eavSetupFactory,
    ) {
        $this->moduleDataSetup      = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->eavSetupFactory      = $eavSetupFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $setup = $this->moduleDataSetup;

        /**
         * Product attribute
         */
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->removeAttribute(Product::ENTITY, 'mp_exclude_sitemap');
        $eavSetup->addAttribute(Product::ENTITY, 'mp_exclude_sitemap', [
            'type'                    => 'varchar',
            'backend'                 => '',
            'frontend'                => '',
            'label'                   => 'Exclude Sitemap',
            'note'                    => 'Added by Mageplaza Sitemap',
            'input'                   => 'select',
            'class'                   => '',
            'source'                  => MpBoolean::class,
            'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible'                 => true,
            'required'                => false,
            'user_defined'            => false,
            'default'                 => 0,
            'searchable'              => false,
            'filterable'              => false,
            'comparable'              => false,
            'visible_on_front'        => false,
            'used_in_product_listing' => true,
            'unique'                  => false,
            'group'                   => 'Search Engine Optimization',
            'sort_order'              => 150,
            'apply_to'                => '',
        ]);
        $eavSetup->removeAttribute(Product::ENTITY, 'mp_sitemap_active_config');
        $eavSetup->addAttribute(Product::ENTITY, 'mp_sitemap_active_config', [
            'type'                    => 'varchar',
            'backend'                 => '',
            'frontend'                => '',
            'label'                   => '',
            'input'                   => 'select',
            'class'                   => '',
            'source'                  => Boolean::class,
            'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible'                 => true,
            'required'                => false,
            'user_defined'            => false,
            'default'                 => 1,
            'searchable'              => false,
            'filterable'              => false,
            'comparable'              => false,
            'visible_on_front'        => false,
            'used_in_product_listing' => true,
            'unique'                  => false,
            'group'                   => 'Search Engine Optimization',
            'sort_order'              => 160,
            'apply_to'                => '',
        ]);

        /**
         * Category attribute
         */
        /** @var CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);

        $categorySetup->removeAttribute(Category::ENTITY, 'mp_exclude_sitemap');
        $categorySetup->addAttribute(Category::ENTITY, 'mp_exclude_sitemap', [
            'type'       => 'int',
            'label'      => '',
            'input'      => 'select',
            'source'     => Boolean::class,
            'required'   => false,
            'sort_order' => 100,
            'global'     => ScopedAttributeInterface::SCOPE_STORE,
            'group'      => 'Search Engine Optimization',
        ]);
        $categorySetup->removeAttribute(Category::ENTITY, 'mp_sitemap_active_config');
        $categorySetup->addAttribute(Category::ENTITY, 'mp_sitemap_active_config', [
            'type'         => 'int',
            'label'        => '',
            'input'        => 'select',
            'source'       => Boolean::class,
            'default'      => '1',
            'visible'      => true,
            'required'     => false,
            'user_defined' => false,
            'sort_order'   => 105,
            'global'       => ScopedAttributeInterface::SCOPE_STORE,
            'group'        => 'Search Engine Optimization',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $setup = $this->moduleDataSetup;
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->removeAttribute(Product::ENTITY, 'mp_exclude_sitemap');
        $eavSetup->removeAttribute(Product::ENTITY, 'mp_sitemap_active_config');

        /** @var CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
        $categorySetup->removeAttribute(Category::ENTITY, 'mp_exclude_sitemap');
        $categorySetup->removeAttribute(Category::ENTITY, 'mp_sitemap_active_config');
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
