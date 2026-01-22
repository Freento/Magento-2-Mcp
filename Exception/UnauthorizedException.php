<?php
declare(strict_types=1);

namespace Freento\Mcp\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class UnauthorizedException extends LocalizedException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct(new Phrase($message));
    }
}
