<?php
declare(strict_types=1);

namespace Freento\Mcp\Ui\DataProvider;

use Freento\Mcp\Model\ResourceModel\AclRole\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Ui\DataProvider\AbstractDataProvider;

class AclRoleListingDataProvider extends AbstractDataProvider
{
    private ResourceConnection $resourceConnection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        ResourceConnection $resourceConnection,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->resourceConnection = $resourceConnection;
    }

    public function getData(): array
    {
        $data = parent::getData();

        // Add tools_count and users_count for each role
        $connection = $this->resourceConnection->getConnection();
        $toolsTable = $this->resourceConnection->getTableName('freento_mcp_acl_role_tool');
        $tokensTable = $this->resourceConnection->getTableName('freento_mcp_user_token');

        foreach ($data['items'] as &$item) {
            $roleId = $item['role_id'];

            // Tools count
            if ($item['access_type'] === 'all') {
                $item['tools_count'] = '—';
            } else {
                $select = $connection->select()
                    ->from($toolsTable, ['COUNT(*)'])
                    ->where('role_id = ?', $roleId);
                $item['tools_count'] = (int)$connection->fetchOne($select);
            }

            // Users count
            $select = $connection->select()
                ->from($tokensTable, ['COUNT(*)'])
                ->where('role_id = ?', $roleId);
            $item['users_count'] = (int)$connection->fetchOne($select);
        }

        return $data;
    }
}
