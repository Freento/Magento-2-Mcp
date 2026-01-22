<?php
declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\ToolResultInterface;

class ToolResultFactory
{
    public function create(array $content): ToolResultInterface
    {
        return new ToolResult($content);
    }

    public function createText(string $text): ToolResultInterface
    {
        return new ToolResult([
            ['type' => 'text', 'text' => $text]
        ]);
    }

    public function createError(string $message): ToolResultInterface
    {
        return new ToolResult(
            [['type' => 'text', 'text' => $message]],
            true
        );
    }
}
