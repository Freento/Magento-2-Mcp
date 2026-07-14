<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\Schema;
use Magento\Framework\DB\Select;

class QuoteResource extends AbstractResource
{
    /**
     * @inheritDoc
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        // Join quote_item to get items count and total qty
        $quoteItemTable = $this->resourceConnection->getTableName('quote_item');
        $select->joinLeft(
            ['items' => new \Zend_Db_Expr(
                "(SELECT quote_id, COUNT(*) as items_count, SUM(qty) as items_qty
                  FROM {$quoteItemTable}
                  WHERE parent_item_id IS NULL
                  GROUP BY quote_id)"
            )],
            'main_table.entity_id = items.quote_id',
            ['items_count', 'items_qty']
        );
    }

    /**
     * @inheritDoc
     */
    protected function fetchAll(Select $select, Schema $schema, array $arguments): array
    {
        $rows = parent::fetchAll($select, $schema, $arguments);
        $firstRow = current($rows);
        $keys = array_keys($firstRow);
        if (
            !in_array('is_active', $keys)
            && !in_array('items_count', $keys)
        ) {
            return $rows;
        }

        foreach ($rows as &$row) {
            $row['status_label'] = ($row['is_active'] ?? false) ? 'Active' : 'Converted to Order';
            $row['items_count'] = $row['items_count'] ?? 0;
        }

        return $rows;
    }
}
