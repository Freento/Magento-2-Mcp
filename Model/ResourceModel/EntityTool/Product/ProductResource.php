<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool\Product;

use Freento\Mcp\Model\EntityTool\ConditionApplier;
use Freento\Mcp\Model\EntityTool\ListResult;
use Freento\Mcp\Model\EntityTool\ListResultFactory;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\LinkField;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ProductResource extends AbstractResource
{
    protected const EAV_ATTRIBUTES = [
        'name', 'price', 'cost', 'special_price', 'special_from_date', 'special_to_date', 'status', 'visibility'
    ];

    private const CATEGORY_TABLE = 'catalog_category_product';
    private const CATEGORY_TABLE_ALIAS = 'cat_group';
    private const CATEGORY_COLUMN = self::CATEGORY_TABLE_ALIAS . '.category_id';

    /** @var array<string, Attribute>|null */
    private ?array $attributes = null;

    /** @var array Filter arguments from current request */
    private array $requestedFilters = [];

    /** @var bool Whether current request is aggregate mode */
    private bool $isAggregate = false;

    /** @var Select|null Outer query for wrapping subquery */
    private ?Select $outerSelect = null;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ConditionApplier $conditionApplier
     * @param ListResultFactory $listResultFactory
     * @param DateTimeHelper $dateTimeHelper
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param LinkField $linkField
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ConditionApplier $conditionApplier,
        ListResultFactory $listResultFactory,
        DateTimeHelper $dateTimeHelper,
        private readonly AttributeCollectionFactory $attributeCollectionFactory,
        private readonly LinkField $linkField
    ) {
        parent::__construct($resourceConnection, $conditionApplier, $listResultFactory, $dateTimeHelper);
    }

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
        $this->requestedFilters = $filters;
        $this->isAggregate = !empty($aggregations);
        $this->outerSelect = $this->resourceConnection->getConnection()->select();

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
            $this->requestedFilters = [];
            $this->isAggregate = false;
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        if ($this->isGroupedByCategory($select)) {
            $categoryTable = $this->resourceConnection->getTableName(self::CATEGORY_TABLE);
            $alias = self::CATEGORY_TABLE_ALIAS;
            $select->joinLeft(
                [$alias => $categoryTable],
                "main_table.entity_id = $alias.product_id",
                []
            );
        }

        // Subquery: join only EAV attributes needed for filtering
        $filterAttributes = array_values(array_intersect(
            static::EAV_ATTRIBUTES,
            array_keys($this->requestedFilters)
        ));
        $this->applyEavJoins($select, $schema, $addJoinedFieldsToSelect, $filterAttributes);

        // Outer query: join remaining EAV attributes for display columns
        if ($addJoinedFieldsToSelect && !$this->isAggregate) {
            // The outer display joins reference the subquery link field, so expose it there.
            $this->applyLinkFieldPassthrough($select);

            $displayAttributes = array_values(array_diff(
                static::EAV_ATTRIBUTES,
                array_keys($this->requestedFilters)
            ));

            $this->applyEavJoins($this->getOuterSelect(), $schema, $addJoinedFieldsToSelect, $displayAttributes);
        }

        if ($addJoinedFieldsToSelect && !$this->isAggregate) {
            $this->applyCategoryListColumn($this->getOuterSelect());
        }
    }

    /**
     * Expose all category IDs of each product as a comma-separated value on the outer SELECT.
     *
     * Uses a correlated subquery that runs only over the already-LIMITed rows, so there is
     * no main JOIN and no row inflation in list mode.
     *
     * @param Select $outerSelect
     */
    private function applyCategoryListColumn(Select $outerSelect): void
    {
        $categoryTable = $this->resourceConnection->getTableName(self::CATEGORY_TABLE);
        $outerSelect->columns([
            'category_id' => new \Zend_Db_Expr(
                "(SELECT GROUP_CONCAT(category_id ORDER BY category_id) "
                . "FROM {$categoryTable} WHERE product_id = main_table.entity_id)"
            )
        ]);
    }

    /**
     * Whether the current query groups by category_id (and therefore needs the category join)
     *
     * @param Select $select
     * @return bool
     * @throws \Zend_Db_Select_Exception
     */
    private function isGroupedByCategory(Select $select): bool
    {
        $groupParts = $select->getPart(Select::GROUP) ?: [];

        return in_array(self::CATEGORY_COLUMN, $groupParts, true);
    }

    /**
     * @inheritDoc
     */
    protected function getGroupByExpression(Schema $schema, string $groupBy)
    {
        if ($groupBy === 'category_id') {
            return self::CATEGORY_COLUMN;
        }

        return parent::getGroupByExpression($schema, $groupBy);
    }

    /**
     * Expose the link field on the subquery so the outer EAV joins can reference it
     *
     * @param Select $select
     * @throws \Exception
     */
    private function applyLinkFieldPassthrough(Select $select): void
    {
        $linkField = $this->linkField->forProduct();
        if ($linkField === 'entity_id') {
            return;
        }

        $select->columns([$linkField => "main_table.$linkField"]);
    }

    /**
     * Apply EAV attribute joins (default scope, store_id = 0)
     *
     * @param Select $select
     * @param Schema $schema
     * @param bool $addJoinedFieldsToSelect
     * @param string[] $attributeCodes Attribute codes to join (empty = all from EAV_ATTRIBUTES)
     */
    protected function applyEavJoins(
        Select $select,
        Schema $schema,
        bool $addJoinedFieldsToSelect,
        array $attributeCodes = []
    ): void {
        $attributes = $this->getAttributes();
        foreach ($attributeCodes as $attributeCode) {
            $field = $schema->getField($attributeCode);
            if ($field === null || !isset($attributes[$attributeCode])) {
                continue;
            }

            $attribute = $attributes[$attributeCode];
            $backendType = $attribute->getBackendType();

            if ($backendType === 'static') {
                continue;
            }

            $this->joinEavAttribute($select, $attribute, $attributeCode, $addJoinedFieldsToSelect);
        }
    }

    /**
     * Join EAV attribute table to SELECT (default scope, store_id = 0)
     *
     * @param Select $select
     * @param Attribute $attribute
     * @param string $attributeCode
     * @param bool $addColumns
     */
    protected function joinEavAttribute(
        Select $select,
        Attribute $attribute,
        string $attributeCode,
        bool $addColumns = false
    ): void {
        $backendType = $attribute->getBackendType();
        $eavTable = $this->resourceConnection->getTableName("catalog_product_entity_$backendType");
        $alias = $this->getEavAttrTableAlias($attributeCode);
        $attributeId = (int)$attribute->getAttributeId();

        $columns = [];
        if ($addColumns) {
            $columns[$attributeCode] = $this->getEavValueColumn($attributeCode);
        }

        $select->joinLeft(
            [$alias => $eavTable],
            $this->buildEavJoinCondition($alias, $attributeId, 0),
            $columns
        );
    }

    /**
     * Build EAV join condition string
     *
     * @param string $eavTable
     * @param int $attributeId
     * @param int $storeId
     * @return string
     * @throws \Exception
     */
    protected function buildEavJoinCondition(string $eavTable, int $attributeId, int $storeId): string
    {
        $linkField = $this->linkField->forProduct();

        return "main_table.$linkField = $eavTable.$linkField"
            . " AND $eavTable.attribute_id = $attributeId"
            . " AND $eavTable.store_id = $storeId";
    }

    /**
     * Get eav attribute table alias
     *
     * @param string $attributeCode
     * @param int|null $storeId
     * @return string
     */
    protected function getEavAttrTableAlias(string $attributeCode, ?int $storeId = null): string
    {
        return 'eav_' . $attributeCode . ($storeId !== null ? ('_' . $storeId) : '');
    }

    /**
     * @inheritDoc
     * @throws \Zend_Db_Select_Exception
     */
    protected function applyFilters(Select $select, Schema $schema, array $arguments): array
    {
        $appliedFilters = parent::applyFilters($select, $schema, $arguments);

        $categoryAppliedFilter = $this->applyCategoryFilter($select, $schema, $arguments);
        if ($categoryAppliedFilter !== null) {
            $appliedFilters[] = $categoryAppliedFilter;
        }

        $fromTables = $select->getPart('from');
        if (!$fromTables || count($fromTables) <= 1) {
            return $appliedFilters;
        }

        foreach ($arguments as $fieldName => $filterValue) {
            if (!in_array($fieldName, static::EAV_ATTRIBUTES)) {
                continue;
            }

            $field = $schema->getField($fieldName);
            if (!$field->isFilterable() || $field->getColumn() !== false) {
                continue;
            }

            $tableAlias = $this->getEavAttrTableAlias($fieldName);
            // Check if table is joined
            if (!isset($fromTables[$tableAlias])) {
                continue;
            }

            $filterCondition = $this->conditionApplier->buildCondition(
                $this->getEavValueColumn($fieldName),
                $filterValue,
                $field->getType()
            );

            if ($filterCondition) {
                $select->where($filterCondition);
                $appliedFilters[] = $this->getAppliedFilterResultString($fieldName, $filterValue);
            }
        }

        return $appliedFilters;
    }

    /**
     * Apply category_id filter
     *
     * Two paths:
     * - When catalog_category_product is already joined (group_by: category_id),
     *   the condition is applied directly to the joined column so that GROUP BY
     *   only sees the filtered categories.
     * - Otherwise, an EXISTS subquery is used to avoid main JOIN row inflation.
     *
     * @param Select $select
     * @param Schema $schema
     * @param array $arguments
     * @return string|null Applied filter description, or null if no filter was applied
     * @throws \Zend_Db_Select_Exception
     */
    protected function applyCategoryFilter(Select $select, Schema $schema, array $arguments): ?string
    {
        $value = $arguments['category_id'] ?? null;
        if ($value === null || $value === '') {
            return null;
        }

        $field = $schema->getField('category_id');
        if ($field === null || !$field->isFilterable()) {
            return null;
        }

        // When the category table is already joined for grouping, filter the joined column
        // directly so GROUP BY only sees the requested categories. Otherwise constrain via an
        // EXISTS subquery to avoid main JOIN row inflation.
        $condition = $this->isGroupedByCategory($select)
            ? $this->conditionApplier->buildCondition(self::CATEGORY_COLUMN, $value, $field->getType())
            : $this->buildCategoryExistsCondition($value, $field->getType());

        if ($condition === null) {
            return null;
        }

        $select->where($condition);
        return $this->getAppliedFilterResultString('category_id', $value);
    }

    /**
     * Build an EXISTS condition matching products assigned to the requested category
     *
     * @param mixed $value
     * @param string $type
     * @return string|null Null when the underlying value condition cannot be built
     */
    private function buildCategoryExistsCondition($value, string $type): ?string
    {
        $categoryTable = $this->resourceConnection->getTableName(self::CATEGORY_TABLE);
        $alias = 'category_filter';
        $valueCondition = $this->conditionApplier->buildCondition("$alias.category_id", $value, $type);
        if ($valueCondition === null) {
            return null;
        }

        return "EXISTS (SELECT 1 FROM $categoryTable AS $alias WHERE $alias.product_id = main_table.entity_id "
            . "AND $valueCondition)";
    }

    /**
     * @inheritDoc
     *
     * Wraps the filtered/sorted/limited SELECT as subquery, adds display-only EAV JOINs on outer query.
     * EAV display JOINs operate on ~20 rows instead of full product table.
     */
    protected function fetchAll(Select $select, Schema $schema, array $arguments): array
    {
        if ($this->isAggregate) {
            return parent::fetchAll($select, $schema, $arguments);
        }

        $connection = $this->resourceConnection->getConnection();

        $outerSelect = $this->getOuterSelect()->from(
            ['main_table' => new \Zend_Db_Expr('(' . $select->assemble() . ')')],
            ['*']
        );

        return $connection->fetchAll($outerSelect);
    }

    /**
     * Get column expression for EAV attribute value
     *
     * @param string $attributeCode
     * @return string
     */
    protected function getEavValueColumn(string $attributeCode): string
    {
        return $this->getEavAttrTableAlias($attributeCode) . '.value';
    }

    /**
     * Get applied filter as string to add to result array
     *
     * @param string $field
     * @param \Stringable|array $value
     * @return string
     */
    protected function getAppliedFilterResultString(string $field, $value): string
    {
        $resultString = $field . ': ';
        if (is_array($value)) {
            $resultString .= json_encode($value);
        } else {
            $resultString .= $value;
        }

        return $resultString;
    }

    /**
     * Get attributes to join
     *
     * @return array<string, Attribute>
     */
    protected function getAttributes(): array
    {
        if ($this->attributes === null) {
            $this->attributes = [];

            $collection = $this->attributeCollectionFactory->create();
            $collection->addFieldToFilter('attribute_code', ['in' => static::EAV_ATTRIBUTES]);

            foreach ($collection as $attribute) {
                $this->attributes[$attribute->getAttributeCode()] = $attribute;
            }
        }

        return $this->attributes;
    }

    /**
     * Get outer select
     *
     * @return Select
     */
    protected function getOuterSelect(): Select
    {
        if ($this->outerSelect === null) {
            $this->outerSelect = $this->resourceConnection->getConnection()->select();
        }

        return $this->outerSelect;
    }
}
