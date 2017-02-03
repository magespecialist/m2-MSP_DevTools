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

namespace MSP\DevTools\Plugin\Event;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\ConfigInterface;
use MSP\DevTools\Helper\Data;
use MSP\DevTools\Model\EventRegistry;

class ManagerInterfacePlugin
{
    protected $eventRegistry;
    protected $eventConfig;
    protected $helperData;

    protected $isActive = null;

    public function __construct(
        EventRegistry $eventRegistry,
        ConfigInterface $eventConfig,
        Data $helperData
    ) {
        $this->eventRegistry = $eventRegistry;
        $this->eventConfig = $eventConfig;
        $this->helperData = $helperData;
    }

    protected function isThisActive()
    {
        if (is_null($this->isActive)) {
            $this->isActive = false; // This avoids recursion
            $this->isActive = $this->helperData->isActive();
        }

        return $this->isActive;
    }

    public function aroundDispatch(
        ManagerInterface $subject,
        \Closure $proceed,
        $eventName,
        array $data = []
    ) {
        if (!$this->isThisActive()) {
            return $proceed($eventName, $data);
        }

        // Retrieve called observer
        $observers = [];
        $observersConfig = $this->eventConfig->getObservers($eventName);

        $phpStormLinks = [];

        foreach ($observersConfig as $observerConfig) {
            $fileName = $this->helperData->getPhpClassFile($observerConfig['instance']);

            $observers[$observerConfig['name']] = [
                'class' => $observerConfig['instance'],
                'file' => $fileName,
            ];

            if ($this->helperData->getPhpStormEnabled()) {
                $phpStormLinks[] = [
                    'key' => 'Observer "'.$observerConfig['name'].'"',
                    'file' => $fileName,
                    'link' => $this->helperData->getPhpStormUrl($fileName),
                ];
            }
        }

        $this->eventRegistry->start($eventName);
        $res = $proceed($eventName, $data);
        $this->eventRegistry->stop(
            $eventName,
            [
                'observers' => $observers,
                'phpstorm_links' => $phpStormLinks,
            ]
        );

        return $res;
    }
}
