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

namespace MSP\DevTools\Plugin\View;

use Magento\Cms\Block\Widget\Block;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Interception\InterceptorInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Layout;
use MSP\DevTools\Model\BlockProcessor;
use MSP\DevTools\Model\Config;
use MSP\DevTools\Model\ElementRegistry;

class LayoutPlugin
{
    /**
     * @var ElementRegistry
     */
    private $elementRegistry;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var BlockProcessor
     */
    private $blockProcessor;

    public function __construct(
        ElementRegistry $elementRegistry,
        EncoderInterface $encoder,
        DirectoryList $directoryList,
        BlockProcessor $blockProcessor,
        Config $config
    )
    {
        $this->elementRegistry = $elementRegistry;
        $this->encoder = $encoder;
        $this->directoryList = $directoryList;
        $this->config = $config;
        $this->blockProcessor = $blockProcessor;
    }

    /**
     * Inject data-* attribute into html document
     *
     * @param  $html
     * @param  $blockId
     * @param  $name
     * @return string
     */
    protected function injectHtmlAttribute($html, $blockId, $name)
    {
        if (!$html || !$this->config->canInjectCode()) {
            return $html;
        }

        $html = '<!-- START_MSPDEV[' . $blockId . ']: ' . $name . ' -->' . $html
            . '<!-- /END_MSPDEV[' . $blockId . ']: ' . $name . ' -->';

        return $html;
    }

    public function aroundRenderElement(Layout $subject, \Closure $proceed, $name, $useCache = true)
    {
        if (!$this->config->isActive() || !$this->config->canInjectCode()) {
            return $proceed($name, $useCache);
        }

        if ($subject->isUiComponent($name) || $subject->isBlock($name)) {
            return $proceed($name, $useCache);
        }

        if (!$name) {
            $name = strtoupper(uniqid('NONAME_'));
        }

        $this->elementRegistry->start($name);
        $blockId = $this->elementRegistry->getOpId();
        $html = $proceed($name, $useCache);
        $payload = [
            'id' => $blockId,
            'type' => 'container',
        ];
        $this->elementRegistry->stop($name, $payload);
        return $this->blockProcessor->wrapBlock($html, $blockId, $name);
    }
}
