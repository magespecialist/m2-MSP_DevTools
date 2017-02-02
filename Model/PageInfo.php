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

namespace MSP\DevTools\Model;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Profiler\Driver\Standard\Stat;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\LayoutInterface;
use MSP\DevTools\Helper\Data;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Interception\DefinitionInterface;
use Magento\Framework\App\ResourceConnection;

class PageInfo
{
    protected $productMetadataInterface;
    protected $layoutInterface;
    protected $requestInterface;
    protected $httpRequest;
    protected $eventRegistry;
    protected $designInterface;
    protected $elementRegistry;
    protected $dataHelper;
    protected $stat;
    protected $types;
    protected $sql_profiler;
    protected $resource;
    protected $longestQueryTime = 0;
    protected $shortestQueryTime = 100000;
    protected $longestQuery;
    protected $all_queries;

    public function __construct(
        ProductMetadataInterface $productMetadataInterface,
        LayoutInterface $layoutInterface,
        RequestInterface $requestInterface,
        EventRegistry $eventRegistry,
        ElementRegistry $elementRegistry,
        DesignInterface $designInterface,
        Http $httpRequest,
        Data $dataHelper,
        Stat $stat,
        ResourceConnection $resource
    ) {
        $this->productMetadataInterface = $productMetadataInterface;
        $this->layoutInterface = $layoutInterface;
        $this->requestInterface = $requestInterface;
        $this->eventRegistry = $eventRegistry;
        $this->httpRequest = $httpRequest;
        $this->designInterface = $designInterface;
        $this->elementRegistry = $elementRegistry;
        $this->dataHelper = $dataHelper;
        $this->stat = $stat;
        $this->resource = $resource;
    }

    /**
     * Get page information
     * @return array
     */
    public function getPageInfo()
    {
        $layoutUpdates = $this->layoutInterface->getUpdate();
        $request = $this->requestInterface;
        $httpRequest = $this->httpRequest;
        $design = $this->designInterface;

        $themeInheritance = [];

        $theme = $design->getDesignTheme();
        while ($theme) {
            $themeInheritance[] = $theme->getCode();
            $theme = $theme->getParentTheme();
        }
        $this->_initSqlProfilerData();

        $info = [
            'general' => [
                [
                    'id' => 'version',
                    'label' => 'Version',
                    'value' => $this->productMetadataInterface->getVersion(),
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
                    'value' => $layoutUpdates->getHandles(),
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
            'plugins' => $this->getPluginsList(),
            'queries' => $this->all_queries,
            'version' => 2,
        ];

        return $info;
    }


    public function getPluginsList()
    {
        $this->types =  [];
        $pluginList = ObjectManager::getInstance()->get('Magento\Framework\Interception\PluginList\PluginList');
        $reflection = new \ReflectionClass($pluginList);

        $processed = $reflection->getProperty('_processed');
        $processed->setAccessible(true);
        $processed = $processed->getValue($pluginList);


        $inherited = $reflection->getProperty('_inherited');
        $inherited->setAccessible(true);
        $inherited = $inherited->getValue($pluginList);


        $types = [DefinitionInterface::LISTENER_BEFORE=>'before',
            DefinitionInterface::LISTENER_AROUND=>'around',
            DefinitionInterface::LISTENER_AFTER=>'after'];

        /**
         * @see: Magento/Framework/Interception/PluginList/PluginList::_inheritPlugins($type)
         */
        foreach($processed as $currentKey=>$processDef) {
            if(preg_match('/^(.*)_(.*)___self$/', $currentKey, $matches) or preg_match('/^(.*?)_(.*?)_(.*)$/', $currentKey, $matches)) {
                $type= $matches[1];
                $method= $matches[2];
                if(!empty($inherited[$type])) {
                    foreach($processDef as $keyType=>$pluginsNames) {
                        if(!is_array($pluginsNames)) {
                            $pluginsNames = [$pluginsNames];
                        }

                        foreach($pluginsNames as $pluginName) {
                            if(!empty($inherited[$type][$pluginName])) {
                                $this->types[md5($pluginName)] = ['name'=>$pluginName, 'type'=>$type, 'plugin'=>$inherited[$type][$pluginName]['instance'], 'sort_order'=> $inherited[$type][$pluginName]['sortOrder'], 'method'=>$types[$keyType].ucfirst($method)];
                            }
                        }
                    }
                }
            }
        }
        return $this->types;
    }


    public function _initSqlProfilerData()
    {
        $this->all_queries = array();
        $this->sql_profiler = new \Zend_Db_Profiler();
        $this->sql_profiler = $this->resource->getConnection('read')->getProfiler();
        if ($this->sql_profiler->getQueryProfiles() && is_array($this->sql_profiler->getQueryProfiles())) {
            foreach ($this->sql_profiler->getQueryProfiles() as $query) {
                if ($query->getElapsedSecs() > $this->longestQueryTime) {
                    $this->longestQueryTime = $query->getElapsedSecs();
                    $this->longestQuery = $query->getQuery();
                }
                if ($query->getElapsedSecs() < $this->shortestQueryTime) {
                    $this->shortestQueryTime = $query->getElapsedSecs();
                }

                $this->all_queries[] = ['sql' => $this->formatSql($query->getQuery()), 'time' => $query->getElapsedSecs(), 'grade' => 'medium'];
            }
        }
        if ($this->all_queries && !empty($this->all_queries)) {
            $standardDeviation = 0;
            $average = $this->getAverage();
            $squareSum = 0;
            foreach ($this->all_queries as $index=>$query) {
                $squareSum = pow($query['time'] - $average, 2);
            }
            foreach ($this->all_queries as $index=>$query) {
                $squareSum = pow($query['time'] - $average, 2);
            }
            if ($squareSum && $this->getTotalNumQueries()) {
                $standardDeviation = sqrt($squareSum/$this->getTotalNumQueries());
            }
            foreach ($this->all_queries as $index=>$query) {
                if($query['time']<($this->shortestQueryTime+2*$standardDeviation)) {
                    $this->all_queries[$index]['grade'] = 'good';
                } elseif($query['time']>($this->longestQueryTime-2*$standardDeviation)) {
                    $this->all_queries[$index]['grade'] = 'bad';
                }
                $this->all_queries[$index]['time'] = $this->formatSqlTime($query['time']);
            }
        }
    }

    public function getTotalNumQueries($queryType = null)
    {
        return $this->sql_profiler->getTotalNumQueries($queryType);
    }

    public function getAverage() {

        return ($this->getTotalNumQueries() &&  $this->sql_profiler->getTotalElapsedSecs()) ?  $this->sql_profiler->getTotalElapsedSecs()/$this->getTotalNumQueries() : 0;
    }

    public function formatSql($sql)
    {
        $htmlSql = $sql;
        $htmlSql = preg_replace('/\b(SET|AS|ASC|COUNT|DESC|IN|LIKE|DISTINCT|INTO|VALUES|LIMIT)\b/', '<span class="sqlword">\\1</span>', $sql);
        $htmlSql = preg_replace('/\b(UNION ALL|DESCRIBE|SHOW|connect|begin|commit)\b/', '<br/><span class="sqlother">\\1</span>', $htmlSql);
        $htmlSql = preg_replace('/\b(UPDATE|SELECT|FROM|WHERE|LEFT JOIN|INNER JOIN|RIGHT JOIN|ORDER BY|GROUP BY|DELETE|INSERT)\b/', '<br/><span class="sqlmain">\\1</span>', $htmlSql);
        $htmlSql = preg_replace('/^<br\/>/', '', $htmlSql);
        return $htmlSql;
    }

    public function formatSqlTime($time)
    {
        $decimals = 2;
        $formatedTime = number_format(round(1000*$time,$decimals),$decimals);

        return $formatedTime;
    }


}
