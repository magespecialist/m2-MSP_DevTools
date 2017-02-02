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
 * @category  MSP
 * @package   MSP_DevTools
 * @copyright Copyright (c) 2016 IDEALIAGroup srl (http://www.idealiagroup.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace MSP\DevTools\Profiler\Driver\Standard\Output;

use Magento\Framework\Profiler;
use Magento\Framework\Profiler\Driver\Standard\AbstractOutput;
use Magento\Framework\Profiler\Driver\Standard\Stat;

class DevTools extends AbstractOutput
{
    public function display(Stat $stat)
    {
        $profilerInfo = [];

        foreach ($this->_getTimerIds($stat) as $timerId) {
            $timerPath = explode(Profiler::NESTING_SEPARATOR, $stat->fetch($timerId, Stat::ID));
            $timerMageId = md5($stat->fetch($timerId, Stat::ID));

            $profilerInfo[$timerMageId] = [
                // @codingStandardsIgnoreStart
                'name' => $timerPath[count($timerPath) - 1],
                // @codingStandardsIgnoreEnd
                'path' => $timerPath,
                'time' => $stat->fetch($timerId, Stat::TIME),
                'proper_time' => $stat->fetch($timerId, Stat::TIME),
                'avg' => $stat->fetch($timerId, Stat::AVG),
                'count' => $stat->fetch($timerId, Stat::COUNT),
                'emalloc' => $stat->fetch($timerId, Stat::EMALLOC),
                'realmem' => $stat->fetch($timerId, Stat::REALMEM),
            ];
        }

        // Calculate proper timers
        foreach ($profilerInfo as $timerId => $timerInfo) {
            $parentPath = $timerInfo['path'];
            // @codingStandardsIgnoreStart
            if (count($parentPath) > 1) {
                // @codingStandardsIgnoreEnd
                array_pop($parentPath);
                $parentTimerId = md5(implode(Profiler::NESTING_SEPARATOR, $parentPath));
                $profilerInfo[$parentTimerId]['proper_time'] -= $timerInfo['time'];
            }
        }

        foreach ($profilerInfo as $timerId => $timerInfo) {
            $profilerInfo[$timerId]['proper_time'] = intval(1000 * $profilerInfo[$timerId]['proper_time']);
            $profilerInfo[$timerId]['time'] = intval(1000 * $profilerInfo[$timerId]['time']);
        }

        // @codingStandardsIgnoreStart
        echo '<script type="text/javascript">';
        echo 'if (!window.mspDevTools) { window.mspDevTools = {}; }';
        echo 'window.mspDevTools[\'profiler\'] = ' . json_encode($profilerInfo) . ';';
        echo '</script>';
        // @codingStandardsIgnoreEnd
    }
}
