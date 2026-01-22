<?php
declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\UserToken;

use Freento\Mcp\Api\UserTokenRepositoryInterface;
use Freento\Mcp\Model\UserTokenFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class AssignRole extends Action
{
    public const ADMIN_RESOURCE = 'Freento_McpServer::user_tokens';

    private JsonFactory $jsonFactory;
    private UserTokenRepositoryInterface $userTokenRepository;
    private UserTokenFactory $userTokenFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        UserTokenRepositoryInterface $userTokenRepository,
        UserTokenFactory $userTokenFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->userTokenRepository = $userTokenRepository;
        $this->userTokenFactory = $userTokenFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $adminUserId = (int)$this->getRequest()->getParam('admin_user_id');
        $roleId = $this->getRequest()->getParam('role_id');

        // Convert empty string or "0" to null
        $roleId = ($roleId === '' || $roleId === '0' || $roleId === null) ? null : (int)$roleId;

        if (!$adminUserId) {
            return $result->setData([
                'success' => false,
                'message' => __('Invalid user ID.')
            ]);
        }

        try {
            // Try to get existing token record
            try {
                $userToken = $this->userTokenRepository->getByAdminUserId($adminUserId);
            } catch (NoSuchEntityException $e) {
                // Create new record without token (just role assignment)
                $userToken = $this->userTokenFactory->create();
                $userToken->setAdminUserId($adminUserId);
                $userToken->setTokenHash(''); // Empty hash - user needs to generate token
            }

            $userToken->setRoleId($roleId);
            $this->userTokenRepository->save($userToken);

            return $result->setData([
                'success' => true,
                'message' => __('ACL Role has been assigned.')
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => __('Error assigning role: %1', $e->getMessage())
            ]);
        }
    }
}
