<?php
/*
 * Copyright © MageSpecialist - Skeeller srl. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace MSP\DevTools\Model\InjectionRules;

use Magento\Framework\App\Request\Http;
use MSP\DevTools\Api\RuleInterface;

class Livewire implements RuleInterface
{
    private Http $http;

    public function __construct(
        Http $http
    ) {
        $this->http = $http;
    }

    public function execute(): bool
    {
        return $this->http->getHeader('X-Livewire') !== 'true';
    }
}
