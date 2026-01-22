<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\EntityTool;

/**
 * Schema definition for entity list tools.
 *
 * Defines the entity structure: table, fields, and pagination limits.
 * Used by AbstractTool to generate input schema and by AbstractResource for queries.
 *
 * Usage example:
 * ```php
 * return new Schema(
 *     entity: 'order',
 *     table: 'sales_order',
 *     fields: [
 *         new Field(name: 'entity_id', type: 'integer'),
 *         new Field(name: 'status', type: 'string'),
 *         new Field(name: 'grand_total', type: 'currency'),
 *     ],
 *     tableAlias: 'main_table',  // optional, default: 'main_table'
 *     defaultLimit: 50,          // optional, default: 50
 *     maxLimit: 200              // optional, default: 200
 * );
 * ```
 */
class Schema
{
    /** @var array<string, Field> Fields indexed by name for quick lookup */
    private array $fieldsByName;

    /**
     * @param string $entity Entity name (e.g., 'order', 'product') - used in output messages
     * @param string $table Database table name
     * @param Field[] $fields List of field definitions
     * @param string $tableAlias SQL alias for main table (default: 'main_table')
     * @param int $defaultLimit Default pagination limit
     * @param int $maxLimit Maximum allowed limit
     */
    public function __construct(
        private string $entity,
        private string $table,
        private array $fields,
        private string $tableAlias = 'main_table',
        private int $defaultLimit = 50,
        private int $maxLimit = 200
    ) {
        // Build name -> field index for O(1) lookups
        $this->fieldsByName = [];
        foreach ($fields as $field) {
            $this->fieldsByName[$field->getName()] = $field;
        }
    }

    /**
     * Get entity name (e.g., 'order', 'product')
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * Get database table name
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get SQL alias for main table
     */
    public function getTableAlias(): string
    {
        return $this->tableAlias;
    }

    /**
     * Get all field definitions
     *
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get field definition by name
     *
     * @param string $name Field name
     * @return Field|null Field definition or null if not found
     */
    public function getField(string $name): ?Field
    {
        return $this->fieldsByName[$name] ?? null;
    }

    /**
     * Check if field exists in schema
     */
    public function hasField(string $name): bool
    {
        return isset($this->fieldsByName[$name]);
    }

    /**
     * Get default pagination limit
     */
    public function getDefaultLimit(): int
    {
        return $this->defaultLimit;
    }

    /**
     * Get maximum allowed pagination limit
     */
    public function getMaxLimit(): int
    {
        return $this->maxLimit;
    }

    /**
     * Build columns array for SELECT clause
     *
     * Returns columns in format suitable for Zend_Db_Select::columns():
     * - Numeric key: simple column expression (e.g., 'main_table.status')
     * - String key: aliased column (e.g., 'payment_method' => 'payment.method')
     *
     * @return array<string|int, string>
     */
    public function getSelectColumns(): array
    {
        $columns = [];

        foreach ($this->fields as $field) {
            $column = $field->getSelectColumn($this->tableAlias);
            if ($column === null) {
                // Field has no DB column (column: false)
                continue;
            }

            if ($field->getColumn() === true) {
                // Standard column: no alias needed
                $columns[] = $column;
            } else {
                // Custom column: use field name as alias
                $columns[$field->getName()] = $column;
            }
        }

        return $columns;
    }

    /**
     * Get fields that can be used in filters
     *
     * @return Field[]
     */
    public function getFilterableFields(): array
    {
        return array_filter($this->fields, fn(Field $f) => $f->isFilterable());
    }

    /**
     * Get names of fields that can be used for sorting
     *
     * @return string[]
     */
    public function getSortableFieldNames(): array
    {
        $names = [];
        foreach ($this->fields as $field) {
            if ($field->isSortable()) {
                $names[] = $field->getName();
            }
        }
        return $names;
    }

    /**
     * Normalize limit value within allowed bounds
     *
     * @param int $limit Requested limit
     * @return int Normalized limit (between 1 and maxLimit)
     */
    public function normalizeLimit(int $limit): int
    {
        if ($limit < 1) {
            return $this->defaultLimit;
        }
        if ($limit > $this->maxLimit) {
            return $this->maxLimit;
        }
        return $limit;
    }

    /**
     * Get fields that can be used with aggregate functions (SUM, AVG, etc.)
     *
     * @return Field[]
     */
    public function getAggregateFields(): array
    {
        return array_filter($this->fields, fn(Field $f) => $f->allowsAggregate());
    }

    /**
     * Get all available GROUP BY options
     *
     * @return string[] Available group_by values (e.g., ['status', 'month', 'day'])
     */
    public function getGroupByOptions(): array
    {
        $options = [];

        foreach ($this->fields as $field) {
            if (!$field->allowsGroupBy()) {
                continue;
            }

            if ($field->hasGroupByOptions()) {
                foreach ($field->getGroupByOptions() as $type) {
                    $options[] = $type;
                }
            } else {
                $options[] = $field->getName();
            }
        }

        return $options;
    }

    /**
     * Check if GROUP BY option is valid
     */
    public function hasGroupByOption(string $groupBy): bool
    {
        return in_array($groupBy, $this->getGroupByOptions());
    }

    /**
     * Get database field expression for GROUP BY
     *
     * @param string $groupBy GROUP BY option name
     * @return string|null Database column expression or null if not found
     */
    public function getGroupByField(string $groupBy): ?string
    {
        foreach ($this->fields as $field) {
            if (!$field->allowsGroupBy()) {
                continue;
            }

            $name = $field->getName();

            // Time-based grouping: 'month' or 'day' -> use parent field's column
            if ($field->hasGroupByOptions() && in_array($groupBy, $field->getGroupByOptions())) {
                return "{$this->tableAlias}.{$name}";
            }

            // Simple grouping: field name matches group_by
            if ($name === $groupBy) {
                $column = $field->getColumn();
                return is_string($column) ? $column : "{$this->tableAlias}.{$name}";
            }
        }

        return null;
    }

    /**
     * Get time-based grouping type
     *
     * @param string $groupBy GROUP BY option name
     * @return string|null Grouping type or null if simple grouping
     */
    public function getGroupByType(string $groupBy): ?string
    {
        foreach ($this->fields as $field) {
            if ($field->hasGroupByOptions() && in_array($groupBy, $field->getGroupByOptions())) {
                return $groupBy;
            }
        }

        return null;
    }

    /**
     * Get names of currency-type aggregate fields
     *
     * @return string[] Field names with type 'currency'
     */
    public function getCurrencyFieldNames(): array
    {
        $names = [];
        foreach ($this->fields as $field) {
            if ($field->allowsAggregate() && $field->getType() === 'currency') {
                $names[] = $field->getName();
            }
        }
        return $names;
    }

    /**
     * Get sortable aggregate fields
     *
     * @return Field[]
     */
    public function getSortableAggregateFields(): array
    {
        return array_filter(
            $this->fields,
            fn(Field $f) => $f->allowsAggregate() && $f->isSortable()
        );
    }

    /**
     * Get sortable group-by options
     *
     * @return string[] Available sortable group_by values
     */
    public function getSortableGroupByOptions(): array
    {
        $options = [];

        foreach ($this->fields as $field) {
            if (!$field->isSortable() || !$field->allowsGroupBy()) {
                continue;
            }

            if ($field->hasGroupByOptions()) {
                foreach ($field->getGroupByOptions() as $type) {
                    $options[] = $type;
                }
            } else {
                $options[] = $field->getName();
            }
        }

        return $options;
    }
}
