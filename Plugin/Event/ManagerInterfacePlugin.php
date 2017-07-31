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

namespace MSP\DevTools\Plugin\Event;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\ConfigInterface;
use MSP\DevTools\Model\Config;
use MSP\DevTools\Model\EventRegistry;

class ManagerInterfacePlugin
{
    protected $isActive = null;
    protected $lock = false;

    /**
     * @var EventRegistry
     */
    private $eventRegistry;

    /**
     * @var ConfigInterface
     */
    private $eventConfig;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        EventRegistry $eventRegistry,
        ConfigInterface $eventConfig,
        Config $config
    ) {
        $this->eventRegistry = $eventRegistry;
        $this->eventConfig = $eventConfig;
        $this->config = $config;
    }

    protected function isThisActive()
    {
        if (is_null($this->isActive)) {
            $this->isActive = false; // This avoids recursion
            $this->isActive = $this->config->isActive();
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

        if (!$this->lock) {
            $this->lock = true;

            foreach ($observersConfig as $observerConfig) {
                $fileName = $this->config->getPhpClassFile($observerConfig['instance']);

                $observers[$observerConfig['name']] = [
                    'class' => $observerConfig['instance'],
                    'file' => $fileName,
                ];

                if ($this->config->getPhpStormEnabled()) {
                    $phpStormLinks[] = [
                        'key' => 'Observer "' . $observerConfig['name'] . '"',
                        'file' => $fileName,
                        'link' => $this->config->getPhpStormUrl($fileName),
                    ];
                }
            }

            $this->lock = false;
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
