<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\EntityTool;

/**
 * Defines a single field in an entity schema.
 *
 * Used to configure how a field behaves in filtering, sorting, output, and aggregation.
 *
 * Usage example:
 * ```php
 * new Field(
 *     name: 'status',
 *     type: 'string',
 *     description: 'Order status (pending, processing, complete)'
 * )
 * ```
 *
 * For joined fields:
 * ```php
 * new Field(
 *     name: 'customer_name',
 *     column: 'customer.firstname',  // table.column format
 *     sortable: false
 * )
 * ```
 *
 * For aggregate-able fields:
 * ```php
 * new Field(
 *     name: 'grand_total',
 *     type: 'currency',
 *     allowAggregate: true  // Can use SUM, AVG, MIN, MAX
 * )
 * ```
 *
 * For group-by fields:
 * ```php
 * new Field(
 *     name: 'created_at',
 *     type: 'date',
 *     allowGroupBy: true,
 *     groupByOptions: ['month', 'day']  // Time-based grouping
 * )
 * ```
 */
class Field
{
    /**
     * @param string $name Field name (used as key in output and input schema)
     * @param string $type Field type: 'string', 'integer', 'numeric', 'currency', 'date'
     *                     Affects input schema operators and output formatting
     * @param bool|string $column DB column mapping:
     *                            - true: use "{tableAlias}.{name}" (default)
     *                            - false: field has no DB column (computed/virtual)
     *                            - string: custom column expression (e.g., 'other_table.column')
     * @param bool $filter Allow filtering by this field (appears in input schema)
     * @param bool $sortable Allow sorting by this field
     * @param string|null $description Human-readable description for input schema
     * @param bool $allowAggregate Whether field can be used with SUM/AVG/MIN/MAX
     * @param bool $allowGroupBy Whether field can be used for GROUP BY
     * @param array $groupByOptions Additional grouping options (e.g., ['month', 'day'] for dates)
     */
    public function __construct(
        private string $name,
        private string $type = 'string',
        private bool|string $column = true,
        private bool $filter = true,
        private bool $sortable = true,
        private ?string $description = null,
        private bool $allowAggregate = false,
        private bool $allowGroupBy = false,
        private array $groupByOptions = []
    ) {
    }

    /**
     * Get field name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get field type for schema generation and value formatting
     *
     * @return string One of: 'string', 'integer', 'numeric', 'currency', 'date'
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get raw column configuration
     *
     * @return bool|string Column mapping (true, false, or custom string)
     */
    public function getColumn(): bool|string
    {
        return $this->column;
    }

    /**
     * Check if field has a DB column (can be selected)
     */
    public function hasColumn(): bool
    {
        return $this->column !== false;
    }

    /**
     * Check if field can be used in filters
     */
    public function isFilterable(): bool
    {
        return $this->filter;
    }

    /**
     * Check if field can be used for sorting
     * Note: requires a DB column to be sortable
     */
    public function isSortable(): bool
    {
        return $this->sortable && $this->hasColumn();
    }

    /**
     * Get field description for input schema
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get column expression for SELECT clause
     *
     * @param string $tableAlias Main table alias
     * @return string|null Column expression or null if no column
     */
    public function getSelectColumn(string $tableAlias): ?string
    {
        if ($this->column === false) {
            return null;
        }

        // Default: use table alias + field name
        if ($this->column === true) {
            return "{$tableAlias}.{$this->name}";
        }

        // Custom column expression (e.g., 'joined_table.column')
        return $this->column;
    }

    /**
     * Get column expression for WHERE clause
     *
     * @param string $tableAlias Main table alias
     * @return string Column expression for filtering
     */
    public function getFilterColumn(string $tableAlias): string
    {
        // Custom column takes precedence
        if (is_string($this->column)) {
            return $this->column;
        }

        return "{$tableAlias}.{$this->name}";
    }

    /**
     * Check if field can be used with aggregate functions (SUM, AVG, MIN, MAX)
     */
    public function allowsAggregate(): bool
    {
        return $this->allowAggregate;
    }

    /**
     * Check if field can be used for GROUP BY
     */
    public function allowsGroupBy(): bool
    {
        return $this->allowGroupBy;
    }

    /**
     * Get additional grouping options (e.g., ['month', 'day'] for date fields)
     *
     * @return array Grouping options or empty array
     */
    public function getGroupByOptions(): array
    {
        return $this->groupByOptions;
    }

    /**
     * Check if field has additional grouping options
     */
    public function hasGroupByOptions(): bool
    {
        return !empty($this->groupByOptions);
    }
}
