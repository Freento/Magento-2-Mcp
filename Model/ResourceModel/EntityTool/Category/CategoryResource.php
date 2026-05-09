<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool\Category;

use Freento\Mcp\Model\EntityTool\ConditionApplier;
use Freento\Mcp\Model\EntityTool\ListResult;
use Freento\Mcp\Model\EntityTool\ListResultFactory;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class CategoryResource extends AbstractResource
{
    protected const EAV_ATTRIBUTES = ['name', 'is_active', 'include_in_menu'];

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
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ConditionApplier $conditionApplier,
        ListResultFactory $listResultFactory,
        DateTimeHelper $dateTimeHelper,
        private readonly AttributeCollectionFactory $attributeCollectionFactory
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
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        // Subquery: join only EAV attributes needed for filtering
        $filterAttributes = array_values(array_intersect(
            static::EAV_ATTRIBUTES,
            array_keys($this->requestedFilters)
        ));
        $this->applyEavJoins($select, $schema, $addJoinedFieldsToSelect, $filterAttributes);

        // Outer query: join remaining EAV attributes for display columns
        if ($addJoinedFieldsToSelect) {
            $displayAttributes = array_values(array_diff(
                static::EAV_ATTRIBUTES,
                array_keys($this->requestedFilters)
            ));

            $this->applyEavJoins($this->getOuterSelect(), $schema, $addJoinedFieldsToSelect, $displayAttributes);
        }

        $select->distinct(true);
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
        $eavTable = $this->resourceConnection->getTableName("catalog_category_entity_$backendType");
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
     */
    protected function buildEavJoinCondition(string $eavTable, int $attributeId, int $storeId): string
    {
        return "main_table.entity_id = $eavTable.entity_id"
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
     * @inheritDoc
     *
     * Wraps the filtered/sorted/limited SELECT as subquery, adds display-only EAV JOINs on outer query.
     * EAV display JOINs operate on ~50 rows instead of full category table.
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
