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

namespace MSP\DevTools\Plugin\View;

use Magento\Cms\Block\Widget\Block;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Interception\InterceptorInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Layout;
use MSP\DevTools\Helper\Data;
use MSP\DevTools\Model\ElementRegistry;

class LayoutPlugin
{
    protected $elementRegistry;
    protected $encoderInterface;
    protected $directoryList;
    protected $helperData;

    public function __construct(
        ElementRegistry $elementRegistry,
        EncoderInterface $encoderInterface,
        DirectoryList $directoryList,
        Data $helperData
    ) {
        $this->elementRegistry = $elementRegistry;
        $this->encoderInterface = $encoderInterface;
        $this->directoryList = $directoryList;
        $this->helperData = $helperData;
    }

    /**
     * Inject data-* attribute into html document
     * @param $html
     * @param $blockId
     * @return string
     */
    protected function _injectHtmlAttribute($html, $blockId)
    {
        if (!$html) {
            return $html;
        }

        $html = '<!-- MSPDEVTOOLS[' . $blockId . '] -->' . $html . '<!-- /MSPDEVTOOLS[' . $blockId . '] -->';

        return $html;
    }

    public function aroundRenderElement(Layout $subject, \Closure $proceed, $name, $useCache = true)
    {
        if (!$this->helperData->isActive()) {
            return $proceed($name, $useCache);
        }
        
        if ($subject->isUiComponent($name)) {
            return $proceed($name, $useCache);
        }

        if (!$name) {
            $name = strtoupper(uniqid('NONAME_'));
        }

        $this->elementRegistry->start($name);
        $html = $proceed($name, $useCache);

        $payload = [
            'phpstorm_url' => null,
        ];

        if ($subject->isBlock($name)) {
            $block = $subject->getBlock($name);

            $payload['type'] = 'block';
            $payload['class'] = $block instanceof InterceptorInterface ? get_parent_class($block) : get_class($block);
            $payload['template'] = $block->getTemplate();
            if ($payload['template']) {
                $payload['template_file'] = substr($block->getTemplateFile(), strlen($this->directoryList->getRoot()));

                $phpStormUrl = $this->helperData->getPhpStormUrl($payload['template_file']);
                if ($phpStormUrl) {
                    $payload['phpstorm_url'] = $phpStormUrl;
                }
            }
            $payload['cache_key'] = $block->getCacheKey();
            $payload['cache_key_info'] = $block->getCacheKeyInfo();
            $payload['module'] = $block->getModuleName();

            if ($block instanceof Block) {
                $payload['cms_block_id'] = $block->getData('block_id');
            }

        } else {
            $payload['type'] = 'container';
        }

        $blockId = $this->elementRegistry->getOpId();
        $this->elementRegistry->stop($name, $payload);

        return $this->_injectHtmlAttribute($html, $blockId);
    }
}
