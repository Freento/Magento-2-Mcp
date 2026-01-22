<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel;

use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Magento\Framework\DB\Select;

class QuoteResource extends AbstractResource
{
    protected function applyRequiredJoins(Select $select, Schema $schema): void
    {
        // Join quote_item to get items count and total qty
        $quoteItemTable = $this->resourceConnection->getTableName('quote_item');
        $select->joinLeft(
            ['qi' => new \Zend_Db_Expr(
                "(SELECT quote_id, COUNT(*) as items_count, SUM(qty) as total_qty
                  FROM {$quoteItemTable}
                  WHERE parent_item_id IS NULL
                  GROUP BY quote_id)"
            )],
            'main_table.entity_id = qi.quote_id',
            ['items_count' => 'qi.items_count', 'total_qty' => 'qi.total_qty']
        );
    }

    protected function fetchAll(Select $select, Schema $schema, array $arguments): array
    {
        $rows = parent::fetchAll($select, $schema, $arguments);

        foreach ($rows as &$row) {
            $row['status_label'] = $row['is_active'] ? 'Active' : 'Converted to Order';
            $row['items_count'] = $row['items_count'] ?? 0;
            $row['total_qty'] = $row['total_qty'] ?? 0;
        }

        return $rows;
    }
}
