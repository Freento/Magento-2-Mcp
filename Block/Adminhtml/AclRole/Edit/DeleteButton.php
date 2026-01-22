<?php
declare(strict_types=1);

namespace Freento\Mcp\Block\Adminhtml\AclRole\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    private UrlInterface $urlBuilder;
    private RequestInterface $request;

    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $request
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    public function getButtonData(): array
    {
        $roleId = $this->request->getParam('role_id');

        if (!$roleId) {
            return [];
        }

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to delete this role?'),
                $this->urlBuilder->getUrl('*/*/delete', ['role_id' => $roleId])
            ),
            'sort_order' => 20,
        ];
    }
}
