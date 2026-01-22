<?php
declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\ToolInterface;

class ToolRegistry
{
    /**
     * @var ToolInterface[]
     */
    private array $tools;

    /**
     * @param ToolInterface[] $tools
     */
    public function __construct(array $tools = [])
    {
        $this->tools = $tools;
    }

    public function get(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * @return ToolInterface[]
     */
    public function getAll(): array
    {
        return $this->tools;
    }
}
