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

namespace Mageplaza\Sitemap\Ui\DataProvider\Product\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Sitemap\Helper\Data;

/**
 * Class UseConfigSettings
 * @package Mageplaza\Sitemap\Ui\DataProvider\Product\Modifier
 */
class UseConfigSettings extends AbstractModifier
{
    const IS_ACTIVE = 'mp_sitemap_active_config';

    /**
     * @var ArrayManager
     */
    protected $_arrayManager;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var $_meta
     */
    protected $_meta;

    /**
     * UseConfigSettings constructor.
     *
     * @param ArrayManager $_arrayManager
     * @param StoreManagerInterface $_storeManager
     * @param Registry $_registry
     * @param Data $_helper
     */
    public function __construct(
        ArrayManager $_arrayManager,
        StoreManagerInterface $_storeManager,
        Registry $_registry,
        Data $_helper
    ) {
        $this->_arrayManager = $_arrayManager;
        $this->_storeManager = $_storeManager;
        $this->_registry     = $_registry;
        $this->_helper       = $_helper;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @param array $meta
     *
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        if ($this->_storeManager->getStore()->getStoreId() != 0) {
            unset($meta['search-engine-optimization']['children']['container_mp_sitemap_active_config']);
        }

        $this->_meta = $meta;
        $this->customizeEnableField();

        return $this->_meta;
    }

    /**
     * @return $this
     */
    protected function customizeEnableField()
    {
        $groupCode = $this->getGroupCodeByField($this->_meta, 'container_' . static::IS_ACTIVE);
        if (!$groupCode) {
            return $this;
        }

        // enable field
        $containerPath = $this->_arrayManager
            ->findPath('container_' . static::IS_ACTIVE, $this->_meta, null, 'children');
        $this->_meta   = $this->_arrayManager->merge($containerPath, $this->_meta, [
            'children' => [
                static::IS_ACTIVE => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'dataScope'         => static::IS_ACTIVE,
                                'component'         => 'Mageplaza_Sitemap/js/product/use-config-settings',
                                'dataType'          => 'boolean',
                                'default'           => '1',
                                'description'       => 'Use Config Settings',
                                'valueMap'          => [
                                    'false' => '0',
                                    'true'  => '1',
                                ],
                                'exports'           => [
                                    'checked'       => '${$.parentName}.' . static::IS_ACTIVE . ':useConfig',
                                    '__disableTmpl' => ['checked' => false]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return $this;
    }
}
