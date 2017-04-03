<?php
/**
 * MageSpecialist
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magespecialist.it so we can send you a copy immediately.
 *
 * @category   MSP
 * @package    MSP_DevTools
 * @copyright  Copyright (c) 2017 Skeeller srl (http://www.magespecialist.it)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace MSP\DevTools\Plugin\PhpEnvironment;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Profiler\Driver\Standard as StandardProfiler;
use MSP\DevTools\Helper\Data;
use MSP\DevTools\Model\PageInfo;
use Zend\Http\PhpEnvironment\Response;
use Magento\Framework\Json\EncoderInterface;
use MSP\DevTools\Model\ElementRegistry;
use MSP\DevTools\Model\EventRegistry;

class ResponsePlugin
{
    private $encoderInterface;
    private $elementRegistry;
    private $eventRegistry;
    private $pageInfo;
    private $standardProfiler;
    private $http;
    private $helperData;
    private $request;

    public function __construct(
        EncoderInterface $encoderInterface,
        ElementRegistry $elementRegistry,
        EventRegistry $eventRegistry,
        PageInfo $pageInfo,
        Http $http,
        StandardProfiler $standardProfiler,
        RequestInterface $request,
        Data $helperData
    ) {
        $this->encoderInterface = $encoderInterface;
        $this->elementRegistry = $elementRegistry;
        $this->eventRegistry = $eventRegistry;
        $this->pageInfo = $pageInfo;
        $this->standardProfiler = $standardProfiler;
        $this->helperData = $helperData;
        $this->http = $http;
        $this->request = $request;
    }

    public function aroundSendContent(
        Response $subject,
        \Closure $proceed
    ) {
        $res = $proceed();

        if ($this->helperData->canInjectCode()) {
            if ($subject instanceof HttpResponse) {
                $this->elementRegistry->calcTimers();
                $this->eventRegistry->calcTimers();

                $pageInfo = $this->pageInfo->getPageInfo();
                // @codingStandardsIgnoreStart
                echo '<script type="text/javascript">';
                echo 'if (!window.mspDevTools) { window.mspDevTools = {}; }';
                foreach ($pageInfo as $key => $info) {
                    echo 'window.mspDevTools["' . $key . '"] = ' . $this->encoderInterface->encode($info) . ';';
                }
                echo '</script>';

                // We must use superglobals since profiler classes cannot access to object manager or DI system
                // See \MSP\DevTools\Profiler\Driver\Standard\Output\DevTools
                $GLOBALS['msp_devtools_profiler'] = true;
                // @codingStandardsIgnoreEnd


            }
        }

        return $res;
    }
}
