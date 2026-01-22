<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel;

use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Magento\Framework\DB\Select;

class CustomerResource extends AbstractResource
{
    protected function applyRequiredJoins(Select $select, Schema $schema): void
    {
        $customerGroupTable = $this->resourceConnection->getTableName('customer_group');
        $select->joinLeft(
            ['customer_group' => $customerGroupTable],
            'main_table.group_id = customer_group.customer_group_id',
            []
        );
    }
}
