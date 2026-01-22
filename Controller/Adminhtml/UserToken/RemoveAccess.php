<?php
declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\UserToken;

use Freento\Mcp\Api\UserTokenRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class RemoveAccess extends Action
{
    public const ADMIN_RESOURCE = 'Freento_McpServer::user_tokens';

    private JsonFactory $jsonFactory;
    private UserTokenRepositoryInterface $userTokenRepository;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        UserTokenRepositoryInterface $userTokenRepository
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->userTokenRepository = $userTokenRepository;
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
            $this->userTokenRepository->deleteByAdminUserId($adminUserId);

            return $result->setData([
                'success' => true,
                'message' => __('MCP access has been removed for this user.')
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => __('Error removing access: %1', $e->getMessage())
            ]);
        }
    }
}
