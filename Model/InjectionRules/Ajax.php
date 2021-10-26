<?php
/*
 * Copyright © MageSpecialist - Skeeller srl. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace MSP\DevTools\Model\InjectionRules;

use Magento\Framework\App\RequestInterface;
use MSP\DevTools\Api\RuleInterface;

class Ajax implements RuleInterface
{
    private RequestInterface $request;

    public function __construct(
        RequestInterface $request
    ) {
        $this->request = $request;
    }
    public function execute(): bool
    {
        return !$this->request->getParam('isAjax')
            || $this->request->getParam('isAjax') === 'false';
    }
}
