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

namespace Mageplaza\Sitemap\Plugin\Model;

use Magento\Cms\Model\GetUtilityPageIdentifiers as CmsGetUtilityPageIdentifiers;
use Mageplaza\Sitemap\Helper\Data;

/**
 * Class GetUtilityPageIdentifiers
 * @package Mageplaza\Sitemap\Plugin\Model
 */
class GetUtilityPageIdentifiers
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * GetUtilityPageIdentifiers constructor.
     *
     * @param Data $helperData
     */
    public function __construct(
        Data $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * @param CmsGetUtilityPageIdentifiers $subject
     * @param array $result
     *
     * @return mixed
     */
    public function afterExecute(CmsGetUtilityPageIdentifiers $subject, $result)
    {
        if (!$this->helperData->isEnableHomepageOptimization() && isset($result[0])) {
            unset($result[0]);
        }

        return $result;
    }
}
