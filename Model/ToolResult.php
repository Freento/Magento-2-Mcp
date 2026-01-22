<?php
declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\ToolResultInterface;

class ToolResult implements ToolResultInterface
{
    private array $content;
    private bool $isError;

    public function __construct(array $content, bool $isError = false)
    {
        $this->content = $content;
        $this->isError = $isError;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function isError(): bool
    {
        return $this->isError;
    }

    public function toArray(): array
    {
        $result = ['content' => $this->content];
        if ($this->isError) {
            $result['isError'] = true;
        }
        return $result;
    }
}
