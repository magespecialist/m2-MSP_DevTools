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
 * @category  MSP
 * @package   MSP_DevTools
 * @copyright Copyright (c) 2016 IDEALIAGroup srl (http://www.idealiagroup.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace MSP\DevTools\Plugin\PhpEnvironment;

use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Profiler;
use Magento\Framework\Profiler\Driver\Standard as StandardProfiler;
use MSP\DevTools\Helper\Data;
use MSP\DevTools\Model\PageInfo;
use Zend\Http\PhpEnvironment\Response;
use Magento\Framework\Json\EncoderInterface;
use MSP\DevTools\Model\ElementRegistry;
use MSP\DevTools\Model\EventRegistry;

class ResponsePlugin
{
    protected $encoderInterface;
    protected $elementRegistry;
    protected $eventRegistry;
    protected $pageInfo;
    protected $standardProfiler;
    protected $http;
    protected $helperData;

    public function __construct(
        EncoderInterface $encoderInterface,
        ElementRegistry $elementRegistry,
        EventRegistry $eventRegistry,
        PageInfo $pageInfo,
        Http $http,
        StandardProfiler $standardProfiler,
        Data $helperData
    ) {
        $this->encoderInterface = $encoderInterface;
        $this->elementRegistry = $elementRegistry;
        $this->eventRegistry = $eventRegistry;
        $this->pageInfo = $pageInfo;
        $this->standardProfiler = $standardProfiler;
        $this->helperData = $helperData;
        $this->http = $http;
    }

    public function aroundSendContent(
        Response $subject,
        \Closure $proceed
    ) {
        $res = $proceed();

        if ($this->helperData->isActive()) {
            if (strtolower($this->http->getHeader('X-Requested-With')) != 'xmlhttprequest') {
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
                    // @codingStandardsIgnoreEnd
                }
            }
        }

        return $res;
    }
}
