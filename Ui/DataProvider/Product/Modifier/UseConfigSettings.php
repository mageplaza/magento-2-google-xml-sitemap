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
 * @category  Mageplaza
 * @package   Mageplaza_RewardPointsUltimate
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\Sitemap\Ui\DataProvider\Product\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Form\Field;

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
    protected $arrayManager;

    /**
     * @var $_meta
     */
    protected $_meta;

    /**
     * UseConfigSettings constructor.
     *
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
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
        $this->_meta = $meta;
        $this->customizeEnableFieldField();

        return $this->_meta;
    }

    /**
     * @return $this
     */
    protected function customizeEnableFieldField()
    {
        $groupCode = $this->getGroupCodeByField($this->_meta, 'container_' . static::IS_ACTIVE);
        if (!$groupCode) {
            return $this;
        }

        // enable field
        $containerPath = $this->arrayManager->
        findPath('container_' . static::IS_ACTIVE, $this->_meta, null, 'children');
        $this->_meta   = $this->arrayManager->merge($containerPath, $this->_meta, [
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
                                    'checked' => '${$.parentName}.' . static::IS_ACTIVE . ':useConfig',
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
