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

abstract class AbstractOpsRegistry
{
    protected $stackOps;
    protected $registeredOps;
    protected $tsStart;

    protected $stackable = true;

    public function __construct()
    {
        $this->stackOps = [];
        $this->registeredOps = [];
        $this->tsStart = [];
    }

    /**
     * Start profiling operation
     *
     * @param  $opName
     * @return $this
     */
    public function start($opName)
    {
        if ($this->stackable) {
            $this->stackOps[] = $opName;
            $opId = $this->getOpId();
            $this->tsStart[$opId] = microtime(true);
        }

        return $this;
    }

    /**
     * Get operation id
     *
     * @param  $stack = null
     * @return string
     */
    public function getOpId($stack = null)
    {
        if (is_null($stack)) {
            $stack = $this->getStack();
        }

        return md5(implode('/', $stack));
    }

    /**
     * Stop profiling operation
     *
     * @param  $opName
     * @param  $payload
     * @return $this
     * @throws \Exception
     */
    public function stop($opName, $payload = [])
    {
        if ($this->stackable) {
            $lastOp = $this->stackOps[count($this->stackOps) - 1];
            if ($opName != $lastOp) {
                throw new \Exception('Invalid operation nesting');
            }

            $opId = $this->getOpId();
            $workTime = microtime(true) - $this->tsStart[$opId];

            $payload['name'] = $opName;
            $payload['stack'] = $this->stackOps;

            if (!isset($this->registeredOps[$opId])) {
                $payload['count'] = 1;
                $payload['time'] = $workTime;
            } else {
                $payload['count'] = $this->registeredOps[$opId]['count'] + 1;
                $payload['time'] = $this->registeredOps[$opId]['time'] + $workTime;
            }

            $payload['proper_time'] = $payload['time'];

            $this->registeredOps[$opId] = $payload;

            array_pop($this->stackOps);
        } else {
            $payload['name'] = $opName;

            if (!isset($this->registeredOps[$opName])) {
                $payload['count'] = 1;
            } else {
                $payload['count'] = $this->registeredOps[$opName]['count'] + 1;
            }

            $this->registeredOps[$opName] = $payload;
        }

        return $this;
    }

    /**
     * Get event path stack
     *
     * @return array
     */
    public function getStack()
    {
        return $this->stackOps;
    }

    /**
     * Get registered ops
     *
     * @return array
     */
    public function getRegisteredOps()
    {
        return $this->registeredOps;
    }

    /**
     * Calculate op timers
     *
     * @return $this
     */
    public function calcTimers()
    {
        foreach ($this->registeredOps as $opId => $registeredOp) {
            $stack = $registeredOp['stack'];

            // @codingStandardsIgnoreStart
            if (count($stack) > 1) {
                $parentStack = $stack;
                array_pop($parentStack);

                $parentOpId = $this->getOpId($parentStack);
                if (isset($this->registeredOps[$parentOpId]['proper_time'])) {
                    $this->registeredOps[$parentOpId]['proper_time'] -= $this->registeredOps[$opId]['time'];
                }
            }
            // @codingStandardsIgnoreEnd
        }

        foreach ($this->registeredOps as $opId => &$registeredOp) {
            $registeredOp['proper_time'] = intval(1000 * $registeredOp['proper_time']);
            $registeredOp['time'] = intval(1000 * $registeredOp['time']);
        }

        return $this;
    }
}
