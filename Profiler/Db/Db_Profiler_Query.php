<?php
/**
 * @author      Benjamin Rosenberger <rosenberger@e-conomix.at>
 * @package
 * @copyright   Copyright (c) 2017 E-CONOMIX GmbH (http://www.e-conomix.at)
 */

namespace MSP\DevTools\Profiler\Db;


class Db_Profiler_Query extends \Zend_Db_Profiler_Query
{
    protected $stacktrace;

    public function setStacktrace($stacktrace) {
        $this->stacktrace = $stacktrace;
    }

    public function getStacktrace() {
        return $this->stacktrace;
    }
}