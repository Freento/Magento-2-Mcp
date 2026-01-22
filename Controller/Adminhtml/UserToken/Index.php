<?php
declare(strict_types=1);

namespace Freento\Mcp\Controller\Adminhtml\UserToken;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Freento_McpServer::user_tokens';

    private PageFactory $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Freento_McpServer::user_tokens');
        $resultPage->getConfig()->getTitle()->prepend(__('MCP User Tokens and Permissions'));

        return $resultPage;
    }
}
