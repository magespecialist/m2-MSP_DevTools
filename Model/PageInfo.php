<?php
/**
 * IDEALIAGroup srl
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@idealiagroup.com so we can send you a copy immediately.
 *
 * @category   MSP
 * @package    MSP_DevTools
 * @copyright  Copyright (c) 2016 IDEALIAGroup srl (http://www.idealiagroup.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace MSP\DevTools\Model;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AppInterface;
use Magento\Framework\Profiler\Driver\Standard\Stat;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\LayoutInterface;
use MSP\DevTools\Helper\Data;

class PageInfo
{
    protected $layoutInterface;
    protected $requestInterface;
    protected $httpRequest;
    protected $eventRegistry;
    protected $designInterface;
    protected $elementRegistry;
    protected $dataHelper;
    protected $stat;

    public function __construct(
        LayoutInterface $layoutInterface,
        RequestInterface $requestInterface,
        EventRegistry $eventRegistry,
        ElementRegistry $elementRegistry,
        DesignInterface $designInterface,
        Http $httpRequest,
        Data $dataHelper,
        Stat $stat
    ) {
        $this->layoutInterface = $layoutInterface;
        $this->requestInterface = $requestInterface;
        $this->eventRegistry = $eventRegistry;
        $this->httpRequest = $httpRequest;
        $this->designInterface = $designInterface;
        $this->elementRegistry = $elementRegistry;
        $this->dataHelper = $dataHelper;
        $this->stat = $stat;
    }

    /**
     * Get page information
     * @return array
     */
    public function getPageInfo()
    {
        $layoutUpdates = $this->layoutInterface->getUpdate();
        $request = $this->requestInterface;
        $httpRequest = $this->httpRequest;
        $design = $this->designInterface;

        $themeInheritance = [];

        $theme = $design->getDesignTheme();
        while ($theme) {
            $themeInheritance[] = $theme->getCode();
            $theme = $theme->getParentTheme();
        }

        $info = [
            'general' => [
                [
                    'id' => 'version',
                    'label' => 'Version',
                    'value' => AppInterface::VERSION
                ], [
                    'id' => 'request',
                    'label' => 'Request',
                    'value' => $request->getParams(),
                    'type' => 'complex'
                ], [
                    'id' => 'action',
                    'label' => 'Action',
                    'value' => $httpRequest->getFullActionName()
                ], [
                    'id' => 'module',
                    'label' => 'Module',
                    'value' => $httpRequest->getModuleName()
                ], [
                    'id' => 'front_name', 'label' => 'Front Name',
                    'value' => $httpRequest->getFrontName()
                ], [
                    'id' => 'path_info', 'label' => 'Path Info',
                    'value' => $httpRequest->getPathInfo()
                ], [
                    'id' => 'original_path_info', 'label' => 'Original Path Info',
                    'value' => $httpRequest->getOriginalPathInfo()
                ], [
                    'id' => 'locale', 'label' => 'Locale',
                    'value' => $design->getLocale(),
                ],
            ],
            'design' => [
                [
                    'id' => 'handles',
                    'label' => 'Layout Handles',
                    'value' => $layoutUpdates->getHandles(),
                    'type' => 'complex'
                ], [
                    'id' => 'theme_code',
                    'label' => 'Theme Code',
                    'value' => $design->getDesignTheme()->getCode(),
                ], [
                    'id' => 'theme_title',
                    'label' => 'Theme Title',
                    'value' => $design->getDesignTheme()->getThemeTitle(),
                ], [
                    'id' => 'theme_inheritance',
                    'label' => 'Theme Inheritance',
                    'value' => $themeInheritance,
                    'type' => 'complex',
                ],
            ],
            'events' => $this->eventRegistry->getRegisteredOps(),
            'blocks' => $this->elementRegistry->getRegisteredOps(),
            'version' => 2,
        ];

        return $info;
    }
}
