<?php
declare(strict_types=1);

namespace Freento\Mcp\Api;

interface ToolInterface
{
    /**
     * Unique tool name
     */
    public function getName(): string;

    /**
     * Description for LLM
     */
    public function getDescription(): string;

    /**
     * JSON Schema for input parameters
     */
    public function getInputSchema(): array;

    /**
     * Execute the tool
     */
    public function execute(array $arguments): ToolResultInterface;
}
