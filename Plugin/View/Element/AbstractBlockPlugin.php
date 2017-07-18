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

namespace MSP\DevTools\Plugin\View\Element;

use Magento\Framework\View\Element\AbstractBlock;
use MSP\DevTools\Model\BlockProcessor;
use MSP\DevTools\Model\Config;
use MSP\DevTools\Model\ElementRegistry;

class AbstractBlockPlugin
{
    /**
     * @var BlockProcessor
     */
    private $blockProcessor;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ElementRegistry
     */
    private $elementRegistry;

    public function __construct(
        ElementRegistry $elementRegistry,
        BlockProcessor $blockProcessor,
        Config $config
    ) {
        $this->blockProcessor = $blockProcessor;
        $this->config = $config;
        $this->elementRegistry = $elementRegistry;
    }

    public function aroundToHtml(AbstractBlock $subject, \Closure $proceed)
    {
        if (!$this->config->isActive() || !$this->config->canInjectCode()) {
            return $proceed();
        }

        $name = $subject->getNameInLayout();

        $this->elementRegistry->start($name);
        $blockId = $this->elementRegistry->getOpId();
        $html = $proceed();
        $payload = $this->blockProcessor->processBlock($subject, $blockId);
        $this->elementRegistry->stop($name, $payload);

        return $this->blockProcessor->wrapBlock($html, $blockId, $name);
    }
}
