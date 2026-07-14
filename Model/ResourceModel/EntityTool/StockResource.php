<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\ConditionApplier;
use Freento\Mcp\Model\EntityTool\ListResultFactory;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class StockResource extends AbstractResource
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param ConditionApplier $conditionApplier
     * @param ListResultFactory $listResultFactory
     * @param DateTimeHelper $dateTimeHelper
     * @param LinkField $linkField
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ConditionApplier $conditionApplier,
        ListResultFactory $listResultFactory,
        DateTimeHelper $dateTimeHelper,
        private readonly LinkField $linkField
    ) {
        parent::__construct($resourceConnection, $conditionApplier, $listResultFactory, $dateTimeHelper);
    }

    /**
     * @inheritDoc
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $select->joinLeft(
            ['product' => $productTable],
            'stock.product_id = product.entity_id',
            []
        );
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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
            $isInStock = (int)($arguments['stock_status'] === 'in_stock');
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

    /**
     * @inheritDoc
     * @throws \Exception
     */
    protected function fetchAll(Select $select, Schema $schema, array $arguments): array
    {
        $rows = parent::fetchAll($select, $schema, $arguments);

        if (empty($rows)) {
            return $rows;
        }

        $entityIds = array_column($rows, 'product_id');
        if (!$entityIds) {
            return $rows;
        }

        $eavAttributeTable = $this->resourceConnection->getTableName('eav_attribute');
        $varcharTable = $this->resourceConnection->getTableName('catalog_product_entity_varchar');
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');

        $connection = $this->resourceConnection->getConnection();
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

        // The varchar EAV table is keyed by the link field, so resolve names through catalog_product_entity
        $linkField = $this->linkField->forProduct();
        $selectNames = $connection->select()
            ->from(['cpe' => $productTable], ['entity_id'])
            ->join(
                ['v' => $varcharTable],
                "v.$linkField = cpe.$linkField",
                ['value']
            )
            ->where('v.attribute_id = ?', $nameAttributeId)
            ->where('v.store_id = 0')
            ->where('cpe.entity_id IN (?)', $entityIds);

        $names = $connection->fetchPairs($selectNames);

        foreach ($rows as &$row) {
            $row['name'] = $names[$row['product_id']] ?? 'N/A';
        }

        return $rows;
    }
}
