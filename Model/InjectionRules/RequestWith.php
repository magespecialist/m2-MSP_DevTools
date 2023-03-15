<?php
/*
 * Copyright © MageSpecialist - Skeeller srl. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace MSP\DevTools\Model\InjectionRules;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use MSP\DevTools\Api\RuleInterface;

class RequestWith implements RuleInterface
{
    private Http $http;
    private RequestInterface $request;

    public function __construct(
        Http $http,
        RequestInterface $request
    ) {
        $this->http = $http;
        $this->request = $request;
    }

    public function execute(): bool
    {
        $header = strtolower($this->http->getHeader('X-Requested-With') ?: '');

        return $header !== 'xmlhttprequest'
            && stripos($header, 'shockwaveflash') === false;
    }
}
