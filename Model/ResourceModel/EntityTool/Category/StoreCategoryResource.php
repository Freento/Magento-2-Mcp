<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool\Category;

use Freento\Mcp\Model\EntityTool\ListResult;
use Freento\Mcp\Model\EntityTool\Schema;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\DB\Select;

/**
 * Store-scoped category resource
 *
 * Extends CategoryResource with store-specific behavior:
 * - Filters categories by store's root category via path matching
 * - Joins store-scoped EAV values with IFNULL fallback to default scope
 */
class StoreCategoryResource extends CategoryResource
{
    /**
     * @var int|null
     */
    private ?int $requestedStoreId = null;

    /**
     * @inheritDoc
     */
    public function getList(
        Schema $schema,
        array $filters = [],
        int $limit = 0,
        int $offset = 0,
        string $sortBy = '',
        string $sortDir = 'DESC',
        array $aggregations = [],
        array $groupBy = []
    ): ListResult {
        $this->requestedStoreId = (int)($filters['store_id'] ?? 0);
        unset($filters['store_id']);

        try {
            return parent::getList(
                $schema,
                $filters,
                $limit,
                $offset,
                $sortBy,
                $sortDir,
                $aggregations,
                $groupBy
            );
        } finally {
            $this->requestedStoreId = null;
        }
    }

    /**
     * @inheritDoc
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        $this->applyStoreFilter($select);
        parent::applyRequiredJoins($select, $schema, $addJoinedFieldsToSelect);
    }

    /**
     * @inheritDoc
     *
     * Adds store-scoped EAV join after default scope join from parent.
     */
    protected function joinEavAttribute(
        Select $select,
        Attribute $attribute,
        string $attributeCode,
        bool $addColumns = false
    ): void {
        parent::joinEavAttribute($select, $attribute, $attributeCode, $addColumns);

        $backendType = $attribute->getBackendType();
        $eavTable = $this->resourceConnection->getTableName("catalog_category_entity_$backendType");
        $storeAlias = $this->getEavAttrTableAlias($attributeCode, $this->requestedStoreId);
        $attributeId = (int)$attribute->getAttributeId();

        $select->joinLeft(
            [$storeAlias => $eavTable],
            $this->buildEavJoinCondition($storeAlias, $attributeId, $this->requestedStoreId),
            []
        );
    }

    /**
     * @inheritDoc
     */
    protected function applyFilters(Select $select, Schema $schema, array $arguments): array
    {
        $appliedFilters = parent::applyFilters($select, $schema, $arguments);

        // Store filter is applied at join level via applyStoreFilter()
        $appliedFilters[] = "store_id: {$this->requestedStoreId}";

        return $appliedFilters;
    }

    /**
     * @inheritDoc
     *
     * Returns IFNULL(store.value, default.value) for store-scoped fallback.
     */
    protected function getEavValueColumn(string $attributeCode): string
    {
        $alias = $this->getEavAttrTableAlias($attributeCode);
        $storeAlias = $this->getEavAttrTableAlias($attributeCode, $this->requestedStoreId);

        return sprintf('IFNULL(%s.value, %s.value)', $storeAlias, $alias);
    }

    /**
     * Apply store filter by joining store → store_group → root category and filtering by path
     *
     * @param Select $select
     */
    private function applyStoreFilter(Select $select): void
    {
        $storeId = $this->requestedStoreId;
        $storeTable = $this->resourceConnection->getTableName('store');
        $storeGroupTable = $this->resourceConnection->getTableName('store_group');
        $categoryTable = $this->resourceConnection->getTableName('catalog_category_entity');

        $select->join(
            ['store' => $storeTable],
            "store.store_id = $storeId",
            []
        )->join(
            ['store_group' => $storeGroupTable],
            'store.group_id = store_group.group_id',
            []
        )->join(
            ['root_cat' => $categoryTable],
            'root_cat.entity_id = store_group.root_category_id',
            []
        );

        $select->where("main_table.path LIKE CONCAT(root_cat.path, '/%')");
    }
}
