<?php
declare(strict_types=1);

namespace Freento\Mcp\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class AccessDeniedException extends LocalizedException
{
    private string $toolName;

    public function __construct(string $toolName)
    {
        $this->toolName = $toolName;
        parent::__construct(new Phrase('Access denied to tool: %1', [$toolName]));
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }
}
