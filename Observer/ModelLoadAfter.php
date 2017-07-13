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

namespace MSP\DevTools\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MSP\DevTools\Model\Config;
use MSP\DevTools\Model\DataModelRegistry;

class ModelLoadAfter implements ObserverInterface
{
    /**
     * @var DataModelRegistry
     */
    private $dataModelRegistry;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        DataModelRegistry $dataModelRegistry,
        Config $config
    ) {
        $this->dataModelRegistry = $dataModelRegistry;
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->config->isActive()) {
            $obj = $observer->getEvent()->getObject();
            if ($obj->getId()) {

                $objName = preg_replace("/\\\\Interceptor$/", "",
                    get_class($obj));

                $objId = md5($objName . '::' . $obj->getId());

                $this->dataModelRegistry->stop($objId, [
                    'model' => $objName . ' (id: ' . $obj->getId() . ')',
                    'data' => $obj->getData()
                ]);
            }
        }
    }
}
