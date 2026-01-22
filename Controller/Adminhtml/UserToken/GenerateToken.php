<?php
declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\UserToken;

use Freento\Mcp\Api\UserTokenRepositoryInterface;
use Freento\Mcp\Model\UserTokenFactory;
use Freento\Mcp\Service\TokenGenerator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class GenerateToken extends Action
{
    public const ADMIN_RESOURCE = 'Freento_McpServer::user_tokens';

    private JsonFactory $jsonFactory;
    private UserTokenRepositoryInterface $userTokenRepository;
    private UserTokenFactory $userTokenFactory;
    private TokenGenerator $tokenGenerator;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        UserTokenRepositoryInterface $userTokenRepository,
        UserTokenFactory $userTokenFactory,
        TokenGenerator $tokenGenerator
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->userTokenRepository = $userTokenRepository;
        $this->userTokenFactory = $userTokenFactory;
        $this->tokenGenerator = $tokenGenerator;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $adminUserId = (int)$this->getRequest()->getParam('admin_user_id');

        if (!$adminUserId) {
            return $result->setData([
                'success' => false,
                'message' => __('Invalid user ID.')
            ]);
        }

        try {
            // Generate new token
            $token = $this->tokenGenerator->generate();
            $tokenHash = $this->tokenGenerator->hash($token);

            // Try to get existing token record
            try {
                $userToken = $this->userTokenRepository->getByAdminUserId($adminUserId);
            } catch (NoSuchEntityException $e) {
                $userToken = $this->userTokenFactory->create();
                $userToken->setAdminUserId($adminUserId);
            }

            $userToken->setTokenHash($tokenHash);
            $this->userTokenRepository->save($userToken);

            return $result->setData([
                'success' => true,
                'message' => __('MCP Token generated successfully!'),
                'token' => $token,
                'notice' => __('Copy this token now. It will not be displayed again.')
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => __('Error generating token: %1', $e->getMessage())
            ]);
        }
    }
}
