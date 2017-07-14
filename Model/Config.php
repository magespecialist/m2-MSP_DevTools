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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Autoload\AutoloaderRegistry;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class Config
{
    const PROTOCOL_VERSION = 3;

    const XML_PATH_GENERAL_ENABLED = 'msp_devtools/general/enabled';
    const XML_PATH_GENERAL_AUTHORIZED_IPS = 'msp_devtools/general/authorized_ranges';

    const XML_PATH_PHPSTORM_ENABLED = 'msp_devtools/phpstorm/enabled';
    const XML_PATH_PHPSTORM_PORT = 'msp_devtools/phpstorm/port';

    protected $canInjectCode;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Http
     */
    private $http;

    /**
     * @var RequestInterface
     */
    private $request;

    protected $isActive = null;
    protected $isEnabled = null;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        RemoteAddress $remoteAddress,
        DirectoryList $directoryList,
        Http $http
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->remoteAddress = $remoteAddress;
        $this->directoryList = $directoryList;
        $this->http = $http;
        $this->request = $request;
    }

    /**
     * Get PHP file path by class name
     * @param string $className
     * @return string
     */
    public function getPhpClassFile($className)
    {
        // @codingStandardsIgnoreStart
        return substr(
            realpath(AutoloaderRegistry::getAutoloader()->findFile($className)),
            strlen($this->directoryList->getRoot())
        );
        // @codingStandardsIgnoreEnd
    }

    /**
     * Return true if phpstorm integration is enabled
     *
     * @return boolean
     */
    public function getPhpStormEnabled()
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_PHPSTORM_ENABLED);
    }

    /**
     * Return true if phpstorm integration port
     *
     * @return int
     */
    public function getPhpStormPort()
    {
        $port = intval($this->scopeConfig->getValue(self::XML_PATH_PHPSTORM_PORT));
        if (!$port) {
            $port = 8091;
        }
        return $port;
    }

    /**
     * Get php storm URL
     *
     * @param  $file
     * @return string|null
     */
    public function getPhpStormUrl($file)
    {
        if (!$this->getPhpStormEnabled()) {
            return null;
        }

        return 'http://127.0.0.1:'.$this->getPhpStormPort().'?message='.urlencode($file);
    }

    /**
     * Return true if devtools are enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        if (is_null($this->isEnabled)) {
            $this->isEnabled = (bool)$this->scopeConfig->getValue(self::XML_PATH_GENERAL_ENABLED);
        }

        return $this->isEnabled;
    }

    /**
     * Return true if IP is in range
     *
     * @param  $ip
     * @param  $range
     * @return bool
     */
    public function getIpInRange($ip, $range)
    {
        if (strpos($range, '/') === false) {
            $range .= '/32';
        }

        list($range, $netmask) = explode('/', $range, 2);
        $rangeDecimal = ip2long($range);
        $ipDecimal = ip2long($ip);
        $wildcardDecimal = pow(2, (32 - $netmask)) - 1;
        $netmaskDecimal = ~$wildcardDecimal;

        return (bool) (($ipDecimal & $netmaskDecimal ) == ($rangeDecimal & $netmaskDecimal));
    }

    /**
     * Return true if IP is matched in a range list
     *
     * @param  $ip
     * @param  array $ranges
     * @return bool
     */
    public function getIpIsMatched($ip, array $ranges)
    {
        foreach ($ranges as $range) {
            if ($this->getIpInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a list of allowed IPs
     *
     * @return array
     */
    public function getAllowedRanges()
    {
        $ranges = $this->scopeConfig->getValue(self::XML_PATH_GENERAL_AUTHORIZED_IPS);
        return preg_split('/\s*[,;]+\s*/', $ranges);
    }

    /**
     * Return true if debugger is active
     *
     * @return boolean
     */
    public function isActive()
    {
        if (is_null($this->isActive)) {
            $this->isActive = false; // Avoid recursion

            if ((php_sapi_name() !== 'cli') && $this->getEnabled()) {
                $ip = $this->remoteAddress->getRemoteAddress();
                $allowedRanges = $this->getAllowedRanges();

                if (count($allowedRanges)) {
                    $this->isActive = $this->getIpIsMatched($ip, $allowedRanges);
                }
            }
        }

        return $this->isActive;
    }

    /**
     * Return true if can inject code
     * @return bool
     */
    public function canInjectCode()
    {
        if (is_null($this->canInjectCode)) {

            $this->canInjectCode = false;

            if ($this->isActive()) {
                $requestedWith = strtolower($this->http->getHeader('X-Requested-With'));

                if (
                    (!$this->request->getParam('isAjax') || ($this->request->getParam('isAjax') == 'false')) &&
                    ($requestedWith != 'xmlhttprequest') &&
                    (strpos($requestedWith, 'shockwaveflash') === false)
                ) {
                    $this->canInjectCode = true;
                }
            }
        }

        return $this->canInjectCode;
    }
}
