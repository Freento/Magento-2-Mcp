<?php
declare(strict_types=1);

namespace Freento\Mcp\Controller\Index;

use Freento\Mcp\Api\McpServerInterface;
use Freento\Mcp\Model\Protocol\ResponseBuilder;
use Freento\Mcp\Service\AclValidator;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private McpServerInterface $mcpServer;
    private RequestInterface $request;
    private JsonFactory $jsonFactory;
    private AclValidator $aclValidator;
    private ResponseBuilder $responseBuilder;

    public function __construct(
        McpServerInterface $mcpServer,
        RequestInterface $request,
        JsonFactory $jsonFactory,
        AclValidator $aclValidator,
        ResponseBuilder $responseBuilder
    ) {
        $this->mcpServer = $mcpServer;
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->aclValidator = $aclValidator;
        $this->responseBuilder = $responseBuilder;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        // Extract Bearer token from Authorization header
        $authHeader = $this->request->getHeader('Authorization');
        $token = $this->extractBearerToken($authHeader ?: null);

        if (!$token) {
            $response = $this->responseBuilder->error(null, -32001, 'Missing or invalid Authorization header');
            return $this->jsonFactory->create()->setData($response);
        }

        // Validate token
        $userToken = $this->aclValidator->validateToken($token);

        if (!$userToken) {
            $response = $this->responseBuilder->error(null, -32001, 'Invalid token');
            return $this->jsonFactory->create()->setData($response);
        }

        // Check if user has a role assigned
        if ($userToken->getRoleId() === null) {
            $response = $this->responseBuilder->error(null, -32002, 'No role assigned to this token');
            return $this->jsonFactory->create()->setData($response);
        }

        $body = $this->request->getContent();
        $response = $this->mcpServer->handle($body, $userToken);

        return $this->jsonFactory->create()
            ->setData($response);
    }

    private function extractBearerToken(?string $authHeader): ?string
    {
        if (!$authHeader) {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
