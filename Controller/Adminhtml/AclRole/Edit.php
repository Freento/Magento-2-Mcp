<?php
declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\AclRole;

use Freento\Mcp\Api\AclRoleRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Freento_McpServer::acl_rules';

    private PageFactory $resultPageFactory;
    private AclRoleRepositoryInterface $roleRepository;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        AclRoleRepositoryInterface $roleRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->roleRepository = $roleRepository;
    }

    public function execute()
    {
        $roleId = (int)$this->getRequest()->getParam('role_id');
        $title = __('New ACL Role');

        if ($roleId) {
            try {
                $role = $this->roleRepository->getById($roleId);
                $title = __('Edit ACL Role: %1', $role->getName());
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This role no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Freento_McpServer::acl_rules');
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
