<?php
declare(strict_types=1);

namespace Freento\Mcp\Ui\Component\Listing\Column;

use Freento\Mcp\Model\ResourceModel\AclRole\CollectionFactory as RoleCollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class UserTokenActions extends Column
{
    private UrlInterface $urlBuilder;
    private RoleCollectionFactory $roleCollectionFactory;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        RoleCollectionFactory $roleCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->roleCollectionFactory = $roleCollectionFactory;
    }

    public function prepare(): void
    {
        parent::prepare();

        // Add roles to JS config
        $roles = $this->roleCollectionFactory->create();
        $roleOptions = [];
        foreach ($roles as $role) {
            $roleOptions[] = [
                'value' => $role->getRoleId(),
                'label' => $role->getName()
            ];
        }

        $config = $this->getData('config');
        $config['roles'] = $roleOptions;
        $this->setData('config', $config);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['user_id'])) {
                    $hasToken = ($item['has_token'] ?? '0') === '1';
                    $userId = (int)$item['user_id'];
                    $username = (string)($item['username'] ?? '');
                    $roleId = $item['role_id'] ?? '';

                    $item[$this->getData('name')] = [
                        'generate_token' => [
                            'href' => '#',
                            'label' => __('Generate Token'),
                            'callback' => [
                                [
                                    'provider' => 'freento_mcp_usertoken_listing.freento_mcp_usertoken_listing'
                                        . '.freento_mcp_usertoken_columns.actions',
                                    'target' => 'generateToken',
                                    'params' => [$userId, $username, $hasToken]
                                ]
                            ]
                        ],
                        'assign_role' => [
                            'href' => '#',
                            'label' => __('Assign Role'),
                            'callback' => [
                                [
                                    'provider' => 'freento_mcp_usertoken_listing.freento_mcp_usertoken_listing'
                                        . '.freento_mcp_usertoken_columns.actions',
                                    'target' => 'assignRole',
                                    'params' => [$userId, $username, $roleId]
                                ]
                            ]
                        ],
                        'remove_access' => [
                            'href' => '#',
                            'label' => __('Remove Access'),
                            'callback' => [
                                [
                                    'provider' => 'freento_mcp_usertoken_listing.freento_mcp_usertoken_listing'
                                        . '.freento_mcp_usertoken_columns.actions',
                                    'target' => 'removeAccess',
                                    'params' => [$userId, $username]
                                ]
                            ]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
