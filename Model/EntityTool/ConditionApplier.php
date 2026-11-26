<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\EntityTool;

use Freento\Mcp\Model\Helper\DateTimeHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Applies filter conditions to database SELECT queries.
 *
 * Converts operator-based filter conditions from MCP input to SQL WHERE clauses.
 * Used by AbstractResource to process filter parameters.
 *
 * Supported operators:
 * - eq, neq: equals, not equals
 * - in, nin: in list, not in list
 * - like, nlike: SQL LIKE patterns (use % as wildcard)
 * - gt, gte, lt, lte: comparison operators
 * - null: IS NULL / IS NOT NULL
 *   (null: true combined with other operators also matches empty values,
 *   e.g. ['gte' => '2024-01-01', 'null' => true] → field >= '2024-01-01' OR field IS NULL)
 *
 * Usage in Resource:
 * ```php
 * // Simple equality
 * $this->conditionApplier->apply($select, 'main_table.status', 'pending');
 *
 * // Operator-based condition
 * $this->conditionApplier->apply($select, 'main_table.status', ['in' => ['pending', 'processing']]);
 *
 * // String with auto-wildcard detection
 * $this->conditionApplier->applyString($select, 'main_table.email', '%@gmail.com');
 *
 * // Date with time normalization
 * $this->conditionApplier->applyDate($select, 'main_table.created_at', ['gte' => '2024-01-01']);
 * ```
 */
class ConditionApplier
{
    private const OPERATOR_NULL = 'null';

    /**
     * @param DateTimeHelper $dateTimeHelper
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly DateTimeHelper $dateTimeHelper,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Apply condition to select query
     *
     * Handles both simple values and operator-based conditions:
     * - Simple value: treated as equals (e.g., 'pending' → status = 'pending')
     * - Operator array: applies each operator (e.g., ['gte' => 100, 'lte' => 500])
     *
     * @param Select $select Database select object
     * @param string $field Full field name with table alias (e.g., 'main_table.status')
     * @param mixed $condition Condition value or array with operators
     */
    public function apply(
        Select $select,
        string $field,
        mixed $condition
    ): void {
        $condition = $this->decodeCondition($condition);

        if (!is_array($condition)) {
            $select->where("{$field} = ?", $condition);
            return;
        }

        if ($this->hasNullAlternative($condition)) {
            $sqlCondition = $this->buildOperatorsCondition($field, $condition);
            if ($sqlCondition !== null) {
                $select->where($sqlCondition);
            }

            return;
        }

        foreach ($condition as $operator => $value) {
            $this->applyOperator($select, $field, strtolower((string)$operator), $value);
        }
    }

    /**
     * Apply string condition with automatic wildcard detection
     *
     * If a simple string value contains '%', it's automatically treated as LIKE condition.
     * This allows LLM to use patterns like '%@gmail.com' without explicit 'like' operator.
     *
     * @param Select $select Database select object
     * @param string $field Full field name with table alias
     * @param mixed $condition Condition value or array with operators
     */
    public function applyString(
        Select $select,
        string $field,
        mixed $condition
    ): void {
        $condition = $this->decodeCondition($condition);

        // Auto-detect wildcards in simple string values
        // e.g., '%@gmail.com' becomes ['like' => '%@gmail.com']
        if (is_string($condition) && str_contains($condition, '%')) {
            $condition = ['like' => $condition];
        }

        $this->apply($select, $field, $condition);
    }

    /**
     * Apply date condition with automatic time normalization and timezone conversion
     *
     * Normalizes date-only values (YYYY-MM-DD) by adding time component:
     * - gte, gt, eq: adds 00:00:00 (start of day)
     * - lte, lt: adds 23:59:59 (end of day)
     *
     * Then converts from store timezone to UTC for database comparison.
     *
     * This ensures date ranges work intuitively:
     * - {'gte': '2024-01-01'} in Europe/Paris → >= '2023-12-31 23:00:00' UTC
     * - {'lte': '2024-01-31'} in Europe/Paris → <= '2024-01-31 22:59:59' UTC
     *
     * @param Select $select Database select object
     * @param string $field Full field name with table alias
     * @param mixed $condition Condition value or array with operators
     */
    public function applyDate(
        Select $select,
        string $field,
        mixed $condition
    ): void {
        $condition = $this->prepareDateCondition($condition);
        $this->apply($select, $field, $condition);
    }

    /**
     * Decode JSON string to array if needed
     *
     * Handles edge case where condition is passed as JSON string
     * (e.g., '{"eq": "pending"}' instead of ['eq' => 'pending'])
     *
     * @param mixed $condition
     * @return mixed
     */
    private function decodeCondition(mixed $condition): mixed
    {
        if (is_string($condition) && (str_starts_with($condition, '{') || str_starts_with($condition, '['))) {
            $decoded = json_decode($condition, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return $condition;
    }

    /**
     * Normalize date by adding time component if missing
     *
     * @param string $date Date string (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
     * @param string $defaultTime Time to append if missing (e.g., '00:00:00' or '23:59:59')
     * @return string Normalized datetime string
     */
    private function normalizeDate(string $date, string $defaultTime): string
    {
        // Check if date is in YYYY-MM-DD format (no time component)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date . ' ' . $defaultTime;
        }
        return $date;
    }

    /**
     * Apply single operator to select query
     *
     * @param Select $select Database select object
     * @param string $field Full field name
     * @param string $operator Operator name (eq, neq, in, etc.)
     * @param mixed $value Operator value
     */
    private function applyOperator(
        Select $select,
        string $field,
        string $operator,
        mixed $value
    ): void {
        $condition = $this->buildOperatorCondition($field, $operator, $value);
        if ($condition) {
            $select->where($condition);
        }
    }

    /**
     * Build SQL condition string for use in JOIN clauses
     *
     * @param string $field Full field name with table alias
     * @param mixed $condition Condition value or array with operators
     * @param string $type Field type: 'string', 'date', 'currency', 'integer'
     * @return string|null SQL condition string or null if no valid condition
     */
    public function buildCondition(string $field, mixed $condition, string $type = 'string'): ?string
    {
        $condition = $this->decodeCondition($condition);

        if ($type === 'date') {
            $condition = $this->prepareDateCondition($condition);
        } elseif ($type === 'string' && is_string($condition) && str_contains($condition, '%')) {
            $condition = ['like' => $condition];
        }

        $connection = $this->resourceConnection->getConnection();

        if (!is_array($condition)) {
            return $connection->quoteInto("$field = ?", $condition);
        }

        return $this->buildOperatorsCondition($field, $condition);
    }

    /**
     * Check if condition combines "null: true" with other operators
     *
     * @param array $condition Array with operators
     * @return bool
     */
    private function hasNullAlternative(array $condition): bool
    {
        if (count($condition) < 2) {
            return false;
        }

        foreach ($condition as $operator => $value) {
            if (strtolower((string)$operator) === self::OPERATOR_NULL && $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build SQL condition string for a set of operators
     *
     * Operators are combined with AND. "null: true" is an alternative to the other operators,
     * so the result also matches rows where the field is empty.
     *
     * @param string $field Full field name with table alias
     * @param array $condition Array with operators
     * @return string|null SQL condition string or null if no valid condition
     */
    private function buildOperatorsCondition(string $field, array $condition): ?string
    {
        $parts = [];
        $nullAlternative = null;
        foreach ($condition as $operator => $value) {
            $operator = strtolower((string)$operator);
            $part = $this->buildOperatorCondition($field, $operator, $value);
            if ($part === null) {
                continue;
            }

            if ($operator === self::OPERATOR_NULL && $value) {
                $nullAlternative = $part;
                continue;
            }

            $parts[] = $part;
        }

        if ($nullAlternative === null) {
            return $parts ? implode(' AND ', $parts) : null;
        }

        if (!$parts) {
            return $nullAlternative;
        }

        return '((' . implode(' AND ', $parts) . ') OR ' . $nullAlternative . ')';
    }

    /**
     * Prepare date condition with normalization and timezone conversion
     *
     * @param mixed $condition
     * @return mixed
     */
    private function prepareDateCondition(mixed $condition): mixed
    {
        $condition = $this->decodeCondition($condition);
        if (is_array($condition)) {
            foreach (['gte', 'eq', 'lt'] as $op) {
                if (isset($condition[$op])) {
                    $normalized = $this->normalizeDate($condition[$op], '00:00:00');
                    $condition[$op] = $this->dateTimeHelper->convertLocalToUtc($normalized);
                }
            }
            foreach (['lte', 'gt'] as $op) {
                if (isset($condition[$op])) {
                    $normalized = $this->normalizeDate($condition[$op], '23:59:59');
                    $condition[$op] = $this->dateTimeHelper->convertLocalToUtc($normalized);
                }
            }
        } elseif (is_string($condition)) {
            $normalized = $this->normalizeDate($condition, '00:00:00');
            $condition = $this->dateTimeHelper->convertLocalToUtc($normalized);
        }

        return $condition;
    }

    /**
     * Build SQL condition string for single operator
     *
     * @param string $field Full field name
     * @param string $operator Operator name
     * @param mixed $value Operator value
     * @return string|null SQL condition or null
     */
    private function buildOperatorCondition(string $field, string $operator, mixed $value): ?string
    {
        $connection = $this->resourceConnection->getConnection();
        return match ($operator) {
            'eq' => $connection->quoteInto("$field = ?", $value),
            'neq' => $connection->quoteInto("$field != ?", $value),
            'gt' => $connection->quoteInto("$field > ?", $value),
            'gte' => $connection->quoteInto("$field >= ?", $value),
            'lt' => $connection->quoteInto("$field < ?", $value),
            'lte' => $connection->quoteInto("$field <= ?", $value),
            'like' => $connection->quoteInto("$field LIKE ?", $value),
            'nlike', 'not_like' => $connection->quoteInto("$field NOT LIKE ?", $value),
            'in' => is_array($value) && !empty($value)
                ? $field . ' IN (' . implode(',', array_map([$connection, 'quote'], $value)) . ')'
                : null,
            'nin', 'not_in' => is_array($value) && !empty($value)
                ? $field . ' NOT IN (' . implode(',', array_map([$connection, 'quote'], $value)) . ')'
                : null,
            self::OPERATOR_NULL => $value ? "$field IS NULL" : "$field IS NOT NULL",
            default => null,
        };
    }
}
