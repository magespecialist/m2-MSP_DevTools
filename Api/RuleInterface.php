<?php
declare(strict_types=1);

namespace MSP\DevTools\Api;

interface RuleInterface
{
    public function execute(): bool;
}
