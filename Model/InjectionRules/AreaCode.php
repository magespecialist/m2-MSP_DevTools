<?php
/*
 * Copyright © MageSpecialist - Skeeller srl. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace MSP\DevTools\Model\InjectionRules;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use MSP\DevTools\Api\RuleInterface;

class AreaCode implements RuleInterface
{
    private State $state;

    public function __construct(
        State $state
    ) {
        $this->state = $state;
    }

    public function execute(): bool
    {
        return $this->state->getAreaCode() !== Area::AREA_GRAPHQL;
    }
}
