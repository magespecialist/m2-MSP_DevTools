<?php
namespace MSP\DevTools\Profiler\Db;
/**
 * @author      Benjamin Rosenberger <rosenberger@e-conomix.at>
 * @package
 * @copyright   Copyright (c) 2017 E-CONOMIX GmbH (http://www.e-conomix.at)
 */

class Db_Profiler extends \Zend_Db_Profiler
{
    public function queryEnd($queryId)
    {
        $result = parent::queryEnd($queryId);
        $this->getQueryProfile($queryId)->setStacktrace((new \Exception)->getTraceAsString());
        return $result;
    }

    public function queryStart($queryText, $queryType = null)
    {
        if (!$this->_enabled) {
            return null;
        }

        // make sure we have a query type
        if (null === $queryType) {
            switch (strtolower(substr(ltrim($queryText), 0, 6))) {
                case 'insert':
                    $queryType = self::INSERT;
                    break;
                case 'update':
                    $queryType = self::UPDATE;
                    break;
                case 'delete':
                    $queryType = self::DELETE;
                    break;
                case 'select':
                    $queryType = self::SELECT;
                    break;
                default:
                    $queryType = self::QUERY;
                    break;
            }
        }

        /**
         * @see Zend_Db_Profiler_Query
         */
        #require_once 'Zend/Db/Profiler/Query.php';
        $this->_queryProfiles[] = new \MSP\DevTools\Profiler\Db\Db_Profiler_Query($queryText, $queryType);

        end($this->_queryProfiles);

        return key($this->_queryProfiles);
    }

}