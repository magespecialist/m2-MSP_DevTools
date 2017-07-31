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
use MSP\DevTools\Model\Config;
use MSP\DevTools\Model\PageInfo;
use Zend\Http\PhpEnvironment\Response;
use Magento\Framework\Json\EncoderInterface;
use MSP\DevTools\Model\ElementRegistry;
use MSP\DevTools\Model\EventRegistry;

class ResponsePlugin
{
    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var ElementRegistry
     */
    private $elementRegistry;

    /**
     * @var EventRegistry
     */
    private $eventRegistry;

    /**
     * @var PageInfo
     */
    private $pageInfo;

    /**
     * @var Http
     */
    private $http;

    /**
     * @var StandardProfiler
     */
    private $standardProfiler;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        EncoderInterface $encoder,
        ElementRegistry $elementRegistry,
        EventRegistry $eventRegistry,
        PageInfo $pageInfo,
        Http $http,
        StandardProfiler $standardProfiler,
        RequestInterface $request,
        Config $config
    ) {
        $this->encoder = $encoder;
        $this->elementRegistry = $elementRegistry;
        $this->eventRegistry = $eventRegistry;
        $this->pageInfo = $pageInfo;
        $this->http = $http;
        $this->standardProfiler = $standardProfiler;
        $this->request = $request;
        $this->config = $config;
    }

    public function aroundSendContent(
        Response $subject,
        \Closure $proceed
    ) {
        $res = $proceed();

        if ($this->config->canInjectCode()) {
            if ($subject instanceof HttpResponse) {
                $this->elementRegistry->calcTimers();
                $this->eventRegistry->calcTimers();

                $pageInfo = $this->pageInfo->getPageInfo();
                // @codingStandardsIgnoreStart
                // Yes, ok, sorry for this... the only way I found to raw output here is to use "echo"
                // Any better idea is highly appreciated.
                echo '<script type="text/javascript">';
                echo 'if (!window.mspDevTools) { window.mspDevTools = {}; }';
                foreach ($pageInfo as $key => $info) {
                    echo 'window.mspDevTools["' . $key . '"] = ' . $this->encoder->encode($info) . ';';
                }
                echo 'window.mspDevTools["_protocol"] = ' . Config::PROTOCOL_VERSION . ';';
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
