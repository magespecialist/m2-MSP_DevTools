<?php
/*
 * Copyright © MageSpecialist - Skeeller srl. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace MSP\DevTools\Model;

use InvalidArgumentException;
use MSP\DevTools\Api\RuleInterface;

class CanInjectCode
{
    private Config $config;

    private array $rules;

    protected ?bool $canInjectCode;

    public function __construct(
        Config $config,
        array $rules = []
    ) {
        $this->config = $config;
        $this->rules = $rules;
        $this->canInjectCode = null;

        foreach ($this->rules as $rule) {
            if (!($rule instanceof RuleInterface)) {
                throw new InvalidArgumentException(
                    'Rule ' . $rule . ' must implements \MSP\DevTools\Api\RuleInterface'
                );
            }
        }
    }

    private function validateRules(): bool
    {
        foreach ($this->rules as $rule) {
            /** @var RuleInterface $rule */
            if (!$rule->execute()) {
                return false;
            }
        }

        return true;
    }
    public function execute(): bool
    {
        if (null === $this->canInjectCode) {

            $this->canInjectCode = false;

            if ($this->config->isActive()) {
                $this->canInjectCode = $this->validateRules();
            }
        }

        return $this->canInjectCode;
    }
}
