<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool\Product;

use Freento\Mcp\Model\EntityTool\ListResult;
use Freento\Mcp\Model\EntityTool\Schema;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\DB\Select;

/**
 * Store-scoped product resource
 *
 * Extends ProductResource with store-specific behavior:
 * - Filters products by website via INNER JOIN on catalog_product_website
 * - Joins store-scoped EAV values with IFNULL fallback to default scope
 */
class StoreProductResource extends ProductResource
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
        $this->applyWebsiteJoin($select);
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
        $eavTable = $this->resourceConnection->getTableName("catalog_product_entity_$backendType");
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

        // Website filter is applied at join level via INNER JOIN in applyWebsiteJoin()
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
     * Apply website filter join via INNER JOIN on catalog_product_website
     *
     * @param Select $select
     */
    private function applyWebsiteJoin(Select $select): void
    {
        $storeId = $this->requestedStoreId;
        $websiteTable = $this->resourceConnection->getTableName('catalog_product_website');
        $store = $this->resourceConnection->getTableName('store');
        $select->join(['cpw' => $websiteTable], 'main_table.entity_id = cpw.product_id', [])
            ->join(['store' => $store], "store.website_id = cpw.website_id AND store.store_id = $storeId", []);
    }
}
