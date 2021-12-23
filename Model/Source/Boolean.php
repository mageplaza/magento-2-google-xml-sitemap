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

namespace Mageplaza\Sitemap\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Boolean
 * @package Mageplaza\Sitemap\Model\Source
 */
class Boolean extends \Magento\Eav\Model\Entity\Attribute\Source\Boolean implements OptionSourceInterface
{
    /**
     * @return array|null
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('No'), 'value' => self::VALUE_NO],
                ['label' => __('Yes'), 'value' => self::VALUE_YES]
            ];
        }
        return $this->_options;
    }
}
