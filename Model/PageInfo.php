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

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Interception\PluginList\PluginList;
use Magento\Framework\Profiler\Driver\Standard\Stat;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\Interception\DefinitionInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Class PageInfo
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PageInfo
{
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var EventRegistry
     */
    private $eventRegistry;

    /**
     * @var ElementRegistry
     */
    private $elementRegistry;

    /**
     * @var DesignInterface
     */
    private $design;

    /**
     * @var Http
     */
    private $httpRequest;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Stat
     */
    private $stat;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var PluginList
     */
    private $pluginList;

    /**
     * @var DataModelRegistry
     */
    private $dataModelRegistry;

    /**
     * @var CollectionRegistry
     */
    private $collectionRegistry;

    /**
     * PageInfo constructor.
     * @param ProductMetadataInterface $productMetadata
     * @param LayoutInterface $layout
     * @param RequestInterface $request
     * @param EventRegistry $eventRegistry
     * @param ElementRegistry $elementRegistry
     * @param DataModelRegistry $dataModelRegistry
     * @param CollectionRegistry $collectionRegistry
     * @param DesignInterface $designInterface
     * @param Http $httpRequest
     * @param Config $config
     * @param Stat $stat
     * @param ResourceConnection $resource
     * @param PluginList $pluginList
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        LayoutInterface $layout,
        RequestInterface $request,
        EventRegistry $eventRegistry,
        ElementRegistry $elementRegistry,
        DataModelRegistry $dataModelRegistry,
        CollectionRegistry $collectionRegistry,
        DesignInterface $designInterface,
        Http $httpRequest,
        Config $config,
        Stat $stat,
        ResourceConnection $resource,
        PluginList $pluginList
    ) {
        $this->productMetadata = $productMetadata;
        $this->layout = $layout;
        $this->request = $request;
        $this->eventRegistry = $eventRegistry;
        $this->elementRegistry = $elementRegistry;
        $this->design = $designInterface;
        $this->httpRequest = $httpRequest;
        $this->config = $config;
        $this->stat = $stat;
        $this->resource = $resource;
        $this->pluginList = $pluginList;
        $this->dataModelRegistry = $dataModelRegistry;
        $this->collectionRegistry = $collectionRegistry;
    }

    /**
     * Get page information
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getPageInfo()
    {
        $request = $this->request;
        $httpRequest = $this->httpRequest;
        $design = $this->design;

        try {
            $layoutUpdates = $this->layout->getUpdate();
            $allLayoutHandles = $layoutUpdates->getHandles();
        } catch (\Exception $e) {
            $allLayoutHandles = [];
        }

        $addedLayoutHandles = [];
        $layoutHandles = [];
        foreach ($allLayoutHandles as $layoutHandle) {
            if (in_array($layoutHandle, ['default', $httpRequest->getFullActionName()])) {
                $layoutHandles[] = $layoutHandle;
            } else {
                $addedLayoutHandles[] = $layoutHandle;
            }
        }

        $themeInheritance = [];

        $theme = $design->getDesignTheme();
        while ($theme) {
            $themeInheritance[] = $theme->getCode();
            $theme = $theme->getParentTheme();
        }

        $info = [
            'general' => [
                [
                    'id' => 'version',
                    'label' => 'Version',
                    'value' => $this->productMetadata->getVersion(),
                ], [
                    'id' => 'request',
                    'label' => 'Request',
                    'value' => $request->getParams(),
                    'type' => 'complex'
                ], [
                    'id' => 'action',
                    'label' => 'Action',
                    'value' => $httpRequest->getFullActionName()
                ], [
                    'id' => 'module',
                    'label' => 'Module',
                    'value' => $httpRequest->getModuleName()
                ], [
                    'id' => 'front_name', 'label' => 'Front Name',
                    'value' => $httpRequest->getFrontName()
                ], [
                    'id' => 'path_info', 'label' => 'Path Info',
                    'value' => $httpRequest->getPathInfo()
                ], [
                    'id' => 'original_path_info', 'label' => 'Original Path Info',
                    'value' => $httpRequest->getOriginalPathInfo()
                ], [
                    'id' => 'locale', 'label' => 'Locale',
                    'value' => $design->getLocale(),
                ],
            ],
            'design' => [
                [
                    'id' => 'handles',
                    'label' => 'Layout Handles',
                    'value' => $layoutHandles,
                    'type' => 'complex'
                ], [
                    'id' => 'additional_handles',
                    'label' => 'Additional Handles',
                    'value' => $addedLayoutHandles,
                    'type' => 'complex'
                ], [
                    'id' => 'theme_code',
                    'value' => $design->getDesignTheme()->getCode(),
                ], [
                    'id' => 'theme_title',
                    'label' => 'Theme Title',
                    'value' => $design->getDesignTheme()->getThemeTitle(),
                ], [
                    'id' => 'theme_inheritance',
                    'label' => 'Theme Inheritance',
                    'value' => $themeInheritance,
                    'type' => 'complex',
                ],
            ],
            'events' => $this->eventRegistry->getRegisteredOps(),
            'blocks' => $this->elementRegistry->getRegisteredOps(),
            'data-models' => $this->dataModelRegistry->getRegisteredOps(),
            'collections' => $this->collectionRegistry->getRegisteredOps(),
            'plugins' => $this->getPluginsList(),
            'queries' => $this->getSqlProfilerData(),
            'version' => 2,
        ];

        return $info;
    }

    /**
     * Get a list of plugins
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getPluginsList()
    {
        $plugins = [];
        // @codingStandardsIgnoreStart
        $reflection = new \ReflectionClass($this->pluginList);
        // @codingStandardsIgnoreEnd

        $processed = $reflection->getProperty('_processed');
        $processed->setAccessible(true);
        $processed = $processed->getValue($this->pluginList);

        $inherited = $reflection->getProperty('_inherited');
        $inherited->setAccessible(true);
        $inherited = $inherited->getValue($this->pluginList);

        $types = [
            DefinitionInterface::LISTENER_BEFORE => 'before',
            DefinitionInterface::LISTENER_AROUND => 'around',
            DefinitionInterface::LISTENER_AFTER => 'after'
        ];

        /**
         * @see: Magento/Framework/Interception/PluginList/PluginList::_inheritPlugins($type)
         */
        foreach ($processed as $currentKey => $processDef) {
            if (preg_match('/^(.*)_(.*)___self$/', $currentKey, $matches) ||
                preg_match('/^(.*?)_(.*?)_(.*)$/', $currentKey, $matches)
            ) {
                $type = $matches[1];
                $method = $matches[2];

                if (!empty($inherited[$type])) {
                    foreach ($processDef as $keyType => $pluginsNames) {
                        if (!is_array($pluginsNames)) {
                            $pluginsNames = [$pluginsNames];
                        }

                        $classMethod = $type . '::' . $method;
                        // @codingStandardsIgnoreStart
                        $key = md5($classMethod);
                        // @codingStandardsIgnoreEnd

                        if (!isset($plugins[$key])) {
                            $fileName = $this->config->getPhpClassFile($type);

                            $plugins[$key] = [
                                'class_method' => $classMethod,
                                'file' => $fileName,
                                'phpstorm_url' => $this->config->getPhpStormUrl($fileName),
                                'plugins' => [],
                                'phpstorm_links' => [],
                            ];

                            if ($this->config->getPhpStormEnabled()) {
                                $plugins[$key]['phpstorm_links'] = [
                                    [
                                        'key' => 'Original Class',
                                        'file' => $fileName,
                                        'link' => $this->config->getPhpStormUrl($fileName),
                                    ],
                                ];
                            }
                        };

                        foreach ($pluginsNames as $pluginName) {
                            if (!empty($inherited[$type][$pluginName])) {
                                $sortOrder = (int) $inherited[$type][$pluginName]['sortOrder'];

                                $fileName = $this->config->getPhpClassFile(
                                    $inherited[$type][$pluginName]['instance']
                                );

                                $plugins[$key]['plugins'][$pluginName] = [
                                    'order' => $sortOrder,
                                    'plugin' => $inherited[$type][$pluginName]['instance'],
                                    'method' => $types[$keyType].ucfirst($method),
                                    'file' => $fileName,
                                ];
                                if ($this->config->getPhpStormEnabled()) {
                                    $plugins[$key]['phpstorm_links'][] = [
                                        'key' => 'Plugin "'.$pluginName.'"',
                                        'file' => $fileName,
                                        'link' => $this->config->getPhpStormUrl($fileName),
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $plugins;
    }

    /**
     * Get a list of plugins
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getSqlProfilerData()
    {
        $allQueries = [];
        $sqlProfiler = $this->resource->getConnection('read')->getProfiler();

        $longestQueryTime = 0;
        $shortestQueryTime = 100000;

        if ($sqlProfiler->getQueryProfiles() && is_array($sqlProfiler->getQueryProfiles())) {
            foreach ($sqlProfiler->getQueryProfiles() as $query) {
                if ($query->getElapsedSecs() > $longestQueryTime) {
                    $longestQueryTime = $query->getElapsedSecs();
                }
                if ($query->getElapsedSecs() < $shortestQueryTime) {
                    $shortestQueryTime = $query->getElapsedSecs();
                }

                if (in_array($query->getQuery(), ['commit', 'begin', 'connect'])) {
                    continue;
                }

                $allQueries[] = [
                    'sql' => $query->getQuery(),
                    'time' => $query->getElapsedSecs(),
                    'grade' => 'medium',
                ];
            }
        }

        if ($allQueries && !empty($allQueries)) {
            $standardDeviation = 0;

            $totalNumQueries = $sqlProfiler->getTotalNumQueries(null);
            $average = ($totalNumQueries && $sqlProfiler->getTotalElapsedSecs()) ?
                $sqlProfiler->getTotalElapsedSecs() / $totalNumQueries : 0;

            $squareSum = 0;

            foreach ($allQueries as $index => $query) {
                $squareSum = pow($query['time'] - $average, 2);
            }

            if ($squareSum && $totalNumQueries) {
                $standardDeviation = sqrt($squareSum / $totalNumQueries);
            }

            foreach ($allQueries as $index => $query) {
                if ($query['time'] < ($shortestQueryTime + 2*$standardDeviation)) {
                    $allQueries[$index]['grade'] = 'good';
                } elseif ($query['time'] > ($longestQueryTime - 2*$standardDeviation)) {
                    $allQueries[$index]['grade'] = 'bad';
                }

                $allQueries[$index]['time'] = $this->formatSqlTime($query['time']);
            }
        }

        return $allQueries;
    }

    public function formatSql($sql)
    {
        $htmlSql = preg_replace(
            '/\b(SET|AS|ASC|COUNT|DESC|IN|LIKE|DISTINCT|INTO|VALUES|LIMIT)\b/',
            '<span class="sqlword">\\1</span>',
            $sql
        );
        $htmlSql = preg_replace(
            '/\b(UNION ALL|DESCRIBE|SHOW|connect|begin|commit)\b/',
            '<br/><span class="sqlother">\\1</span>',
            $htmlSql
        );
        $htmlSql = preg_replace(
            '/\b(UPDATE|SELECT|FROM|WHERE|LEFT JOIN|INNER JOIN|RIGHT JOIN|ORDER BY|GROUP BY|DELETE|INSERT)\b/',
            '<br/><span class="sqlmain">\\1</span>',
            $htmlSql
        );
        $htmlSql = preg_replace('/^<br\/>/', '', $htmlSql);
        return $htmlSql;
    }

    public function formatSqlTime($time)
    {
        $decimals = 2;
        $formattedTime = number_format(round(1000 * $time, $decimals), $decimals);

        return $formattedTime;
    }
}
