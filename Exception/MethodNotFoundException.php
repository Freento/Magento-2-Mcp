<?php
declare(strict_types=1);

namespace Freento\Mcp\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class MethodNotFoundException extends LocalizedException
{
    public function __construct(string $method)
    {
        parent::__construct(new Phrase('Method not found: %1', [$method]));
    }
}
