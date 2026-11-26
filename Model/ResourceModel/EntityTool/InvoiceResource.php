<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\Schema;
use Magento\Framework\DB\Select;

class InvoiceResource extends AbstractResource
{
    /**
     * @inheritDoc
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        $orderTable = $this->resourceConnection->getTableName('sales_order');
        $select->joinLeft(
            ['order' => $orderTable],
            'main_table.order_id = order.entity_id',
            []
        );
    }
}
