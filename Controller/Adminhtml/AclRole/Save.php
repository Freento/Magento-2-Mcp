<?php
declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\AclRole;

use Freento\Mcp\Api\AclRoleRepositoryInterface;
use Freento\Mcp\Model\AclRoleFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Freento_McpServer::acl_rules';

    private AclRoleRepositoryInterface $roleRepository;
    private AclRoleFactory $roleFactory;

    public function __construct(
        Context $context,
        AclRoleRepositoryInterface $roleRepository,
        AclRoleFactory $roleFactory
    ) {
        parent::__construct($context);
        $this->roleRepository = $roleRepository;
        $this->roleFactory = $roleFactory;
    }

    public function execute()
    {
        $postData = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$postData) {
            return $resultRedirect->setPath('*/*/');
        }

        // UI Component form sends data nested under 'data' key
        $data = $postData['data'] ?? $postData;
        $roleId = isset($data['role_id']) && $data['role_id'] !== '' ? (int)$data['role_id'] : null;

        try {
            if ($roleId) {
                $role = $this->roleRepository->getById($roleId);
            } else {
                $role = $this->roleFactory->create();
            }

            $role->setName($data['name'] ?? '');
            $role->setAccessType($data['access_type'] ?? 'specified');

            $this->roleRepository->save($role);

            // Save tools if access type is 'specified'
            $tools = [];
            if ($role->getAccessType() === 'specified' && isset($data['tools'])) {
                $tools = is_array($data['tools']) ? $data['tools'] : [];
            }
            $this->roleRepository->saveRoleTools($role->getRoleId(), $tools);

            $this->messageManager->addSuccessMessage(__('ACL Role has been saved.'));

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['role_id' => $role->getRoleId()]);
            }

            return $resultRedirect->setPath('*/*/');

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the role.'));
        }

        return $resultRedirect->setPath('*/*/edit', ['role_id' => $roleId]);
    }
}
