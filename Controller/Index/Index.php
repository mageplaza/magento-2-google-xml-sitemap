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

namespace Mageplaza\Sitemap\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\Sitemap\Helper\Data as HelperConfig;

/**
 * Class Index
 * @package Mageplaza\Sitemap\Controller\Index
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var HelperConfig
     */
    protected $helperConfig;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param HelperConfig $helperConfig
     */
    public function __construct(Context $context, PageFactory $pageFactory, HelperConfig $helperConfig)
    {
        $this->pageFactory = $pageFactory;
        $this->helperConfig = $helperConfig;

        return parent::__construct($context);
    }

    /**
     * @return Page
     * @throws NotFoundException
     */
    public function execute()
    {
        if (!$this->helperConfig->isEnableHtmlSiteMap()) {
            throw new NotFoundException(__('Parameter is incorrect.'));
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('HTML Sitemap'));

        return $page;
    }
}
