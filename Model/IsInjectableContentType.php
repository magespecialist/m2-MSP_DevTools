<?php
/*
 * Copyright © MageSpecialist - Skeeller srl. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace MSP\DevTools\Model;

use Laminas\Http\PhpEnvironment\Response;

class IsInjectableContentType
{
    private array $contentTypesToSkip;

    public function __construct(
        array $contentTypesToSkip = []
    ) {
        $this->contentTypesToSkip = $contentTypesToSkip;
    }
    public function execute(Response $response)
    {
        $result = true;

        $contentType = $response->getHeader('content-type');
        
        if (false === $contentType) {
            /** sometimes the content-type header is not set */
            return $result;
        }
        
        foreach ($this->contentTypesToSkip as $ct) {
            if ($contentType->match($ct)) {
                $result = false;
                break;
            }
        }
        
        return $result;
    }
}
