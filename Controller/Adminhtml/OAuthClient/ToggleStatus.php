<?php

declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\OAuthClient;

use Freento\Mcp\Model\OAuth\ClientFactory;
use Freento\Mcp\Model\ResourceModel\OAuth\Client as ClientResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class ToggleStatus extends Action implements HttpPostActionInterface, HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Freento_Mcp::oauth_clients';

    /**
     * @param Context $context
     * @param ClientFactory $clientFactory
     * @param ClientResource $clientResource
     */
    public function __construct(
        Context $context,
        private readonly ClientFactory $clientFactory,
        private readonly ClientResource $clientResource
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $entityId = (int)$this->getRequest()->getParam('entity_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$entityId) {
            $this->messageManager->addErrorMessage(__('Invalid client ID.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $client = $this->clientFactory->create();
            $this->clientResource->load($client, $entityId);

            if (!$client->getId()) {
                $this->messageManager->addErrorMessage(__('This client no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }

            $newStatus = !$client->isActive();
            $client->setIsActive($newStatus);
            $this->clientResource->save($client);

            $this->messageManager->addSuccessMessage(
                $newStatus
                    ? __('Client has been activated.')
                    : __('Client has been deactivated.')
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not update client status: %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
