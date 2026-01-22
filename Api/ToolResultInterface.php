<?php
declare(strict_types=1);

namespace Freento\Mcp\Api;

interface ToolResultInterface
{
    public function getContent(): array;

    public function isError(): bool;

    public function toArray(): array;
}
