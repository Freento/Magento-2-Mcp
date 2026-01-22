<?php
declare(strict_types=1);

namespace Freento\Mcp\Model;

use Freento\Mcp\Api\Data\UserTokenInterface;
use Freento\Mcp\Exception\AccessDeniedException;
use Freento\Mcp\Exception\MethodNotFoundException;
use Freento\Mcp\Exception\ToolNotFoundException;
use Freento\Mcp\Service\AclValidator;

class Router
{
    private ToolRegistry $toolRegistry;
    private AclValidator $aclValidator;
    private string $serverInstructions;

    public function __construct(
        ToolRegistry $toolRegistry,
        AclValidator $aclValidator,
        string $serverInstructions = ''
    ) {
        $this->toolRegistry = $toolRegistry;
        $this->aclValidator = $aclValidator;
        $this->serverInstructions = $serverInstructions;
    }

    /**
     * @throws MethodNotFoundException
     * @throws ToolNotFoundException
     * @throws AccessDeniedException
     */
    public function dispatch(string $method, array $params, UserTokenInterface $userToken): array
    {
        return match ($method) {
            'initialize' => $this->handleInitialize(),
            'tools/list' => $this->handleToolsList($userToken),
            'tools/call' => $this->handleToolsCall($params, $userToken),
            default => throw new MethodNotFoundException($method)
        };
    }

    private function handleInitialize(): array
    {
        $result = [
            'protocolVersion' => '2024-11-05',
            'serverInfo' => [
                'name' => 'freento-magento-mcp',
                'version' => '1.0.0'
            ],
            'capabilities' => [
                // empty class is used because the syntax requires {}, whereas an empty array would be serialized to []
                'tools' => new \stdClass()
            ]
        ];

        if ($this->serverInstructions) {
            $result['instructions'] = $this->serverInstructions;
        }

        return $result;
    }

    private function handleToolsList(UserTokenInterface $userToken): array
    {
        $allowedTools = $this->aclValidator->getAllowedTools($userToken);
        $tools = [];

        foreach ($this->toolRegistry->getAll() as $tool) {
            // If allowedTools is null, user has access to all tools
            // Otherwise, filter by allowed tool names
            if ($allowedTools === null || in_array($tool->getName(), $allowedTools, true)) {
                $tools[] = [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'inputSchema' => $tool->getInputSchema()
                ];
            }
        }

        return ['tools' => $tools];
    }

    /**
     * @throws ToolNotFoundException
     * @throws AccessDeniedException
     */
    private function handleToolsCall(array $params, UserTokenInterface $userToken): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        $tool = $this->toolRegistry->get($toolName);

        if (!$tool) {
            throw new ToolNotFoundException($toolName);
        }

        // Check ACL permission for the tool
        if (!$this->aclValidator->canUseTool($userToken, $toolName)) {
            throw new AccessDeniedException($toolName);
        }

        return $tool->execute($arguments)->toArray();
    }
}
