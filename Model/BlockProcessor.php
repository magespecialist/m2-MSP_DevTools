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

namespace MSP\DevTools\Model;

use Magento\Cms\Block\Block;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Interception\InterceptorInterface;
use Magento\Framework\View\Element\AbstractBlock;

class BlockProcessor
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config,
        DirectoryList $directoryList
    ) {

        $this->directoryList = $directoryList;
        $this->config = $config;
    }

    /**
     * Inject start/finish tags into html document
     *
     * @param  $html
     * @param  $blockId
     * @param  $name
     * @return string
     */
    public function wrapBlock($html, $blockId, $name)
    {
        if (trim($html)) {
            $html = '<!-- START_MSPDEV[' . $blockId . ']: ' . $name . ' -->' . $html
            . '<!-- END_MSPDEV[' . $blockId . ']: ' . $name . ' -->';
 
        }
        return $html;
    }

    public function processBlock(AbstractBlock $block, $blockId)
    {
        $name = $block->getNameInLayout();

        if (!$name) {
            $name = strtoupper(uniqid('NONAME_'));
        }

        $payload = [
            'phpstorm_url' => null,
        ];

        $phpStormLinks = [];

        $payload['type'] = 'block';
        $payload['class'] = $block instanceof InterceptorInterface ? get_parent_class($block) : get_class($block);
        $payload['file'] = $this->config->getPhpClassFile($payload['class']);

        if ($this->config->getPhpStormEnabled()) {
            $phpStormLinks[] = [
                'key' => 'Block Class',
                'file' => $payload['file'],
                'link' => $this->config->getPhpStormUrl($payload['file']),
            ];
        }

        $payload['template'] = $block->getTemplate();
        if ($payload['template']) {
            $payload['template_file'] = substr($block->getTemplateFile(), strlen($this->directoryList->getRoot()));
            $phpStormUrl = $this->config->getPhpStormUrl($payload['template_file']);
            if ($phpStormUrl) {
                $payload['phpstorm_url'] = $phpStormUrl;

                if ($this->config->getPhpStormEnabled()) {
                    $phpStormLinks[] = [
                        'key' => 'Template File',
                        'file' => $payload['template_file'],
                        'link' => $this->config->getPhpStormUrl($payload['template_file']),
                    ];
                }
            }
        }

        $payload['cache_key'] = $block->getCacheKey();
        $payload['cache_key_info'] = $block->getCacheKeyInfo();
        $payload['module'] = $block->getModuleName();

        if ($block instanceof Block) {
            $payload['cms_block_id'] = $block->getData('block_id');
        }

        $payload['phpstorm_links'] = $phpStormLinks;
        $payload['id'] = $blockId;

        return $payload;
    }
}
