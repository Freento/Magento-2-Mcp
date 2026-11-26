<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\Schema;
use Magento\Framework\DB\Select;

class CustomerAddressResource extends AbstractResource
{
    /**
     * @inheritDoc
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        $customerTable = $this->resourceConnection->getTableName('customer_entity');
        $select->joinLeft(
            ['customer' => $customerTable],
            'main_table.parent_id = customer.entity_id',
            []
        );
    }
}
