<?php
declare(strict_types=1);

namespace Freento\Mcp\Api;

use Freento\Mcp\Api\Data\UserTokenInterface;

interface McpServerInterface
{
    /**
     * Handle MCP request
     *
     * @param string $jsonRpcRequest
     * @param UserTokenInterface $userToken The authenticated user token with role context
     * @return array JSON-RPC response
     */
    public function handle(string $jsonRpcRequest, UserTokenInterface $userToken): array;
}
