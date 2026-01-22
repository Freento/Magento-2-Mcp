<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel;

use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Magento\Framework\DB\Select;

class StockResource extends AbstractResource
{
    protected function applyRequiredJoins(Select $select, Schema $schema): void
    {
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $select->joinLeft(
            ['product' => $productTable],
            'stock.product_id = product.entity_id',
            []
        );
    }

    protected function applyFilters(Select $select, Schema $schema, array $arguments): array
    {
        $appliedFilters = parent::applyFilters($select, $schema, $arguments);

        // Only simple products have real stock
        $select->where('product.type_id = ?', 'simple');

        if (!empty($arguments['product_ids']) && is_array($arguments['product_ids'])) {
            $ids = array_map('intval', $arguments['product_ids']);
            $select->where('stock.product_id IN (?)', $ids);
            $appliedFilters[] = 'product_ids: ' . implode(',', $ids);
        }

        if (!empty($arguments['stock_status'])) {
            $isInStock = $arguments['stock_status'] === 'in_stock' ? 1 : 0;
            $select->where('stock.is_in_stock = ?', $isInStock);
            $appliedFilters[] = "stock_status: {$arguments['stock_status']}";
        }

        if (isset($arguments['qty_from']) && $arguments['qty_from'] !== '') {
            $select->where('stock.qty >= ?', (float)$arguments['qty_from']);
            $appliedFilters[] = "qty_from: {$arguments['qty_from']}";
        }

        if (isset($arguments['qty_to']) && $arguments['qty_to'] !== '') {
            $select->where('stock.qty <= ?', (float)$arguments['qty_to']);
            $appliedFilters[] = "qty_to: {$arguments['qty_to']}";
        }

        if (!empty($arguments['low_stock'])) {
            $select->where('stock.qty <= stock.min_qty');
            $appliedFilters[] = 'low_stock: true';
        }

        return $appliedFilters;
    }

    protected function fetchAll(Select $select, Schema $schema, array $arguments): array
    {
        $rows = parent::fetchAll($select, $schema, $arguments);

        if (empty($rows)) {
            return $rows;
        }

        $connection = $this->resourceConnection->getConnection();
        $entityIds = array_column($rows, 'product_id');

        $eavAttributeTable = $this->resourceConnection->getTableName('eav_attribute');
        $varcharTable = $this->resourceConnection->getTableName('catalog_product_entity_varchar');

        $selectAttr = $connection->select()
            ->from($eavAttributeTable, ['attribute_id'])
            ->where('attribute_code = ?', 'name')
            ->where('entity_type_id = ?', 4);
        $nameAttributeId = $connection->fetchOne($selectAttr);

        if (!$nameAttributeId) {
            foreach ($rows as &$row) {
                $row['name'] = 'N/A';
            }
            return $rows;
        }

        $selectNames = $connection->select()
            ->from($varcharTable, ['entity_id', 'value'])
            ->where('attribute_id = ?', $nameAttributeId)
            ->where('store_id = 0')
            ->where('entity_id IN (?)', $entityIds);

        $names = $connection->fetchPairs($selectNames);

        foreach ($rows as &$row) {
            $row['name'] = $names[$row['product_id']] ?? 'N/A';
        }

        return $rows;
    }
}
