<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\EntityTool;

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

        foreach ($condition as $operator => $value) {
            $this->applyOperator($select, $field, strtolower($operator), $value);
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
        if (!is_array($condition) && is_string($condition) && str_contains($condition, '%')) {
            $condition = ['like' => $condition];
        }

        $this->apply($select, $field, $condition);
    }

    /**
     * Apply date condition with automatic time normalization
     *
     * Normalizes date-only values (YYYY-MM-DD) by adding time component:
     * - gte, gt, eq: adds 00:00:00 (start of day)
     * - lte, lt: adds 23:59:59 (end of day)
     *
     * This ensures date ranges work intuitively:
     * - {'gte': '2024-01-01'} → >= '2024-01-01 00:00:00'
     * - {'lte': '2024-01-31'} → <= '2024-01-31 23:59:59'
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
        $condition = $this->decodeCondition($condition);

        if (is_array($condition)) {
            // Normalize dates: add time component if missing
            // For "start" operators, use beginning of day
            foreach (['gte', 'gt', 'eq'] as $op) {
                if (isset($condition[$op])) {
                    $condition[$op] = $this->normalizeDate($condition[$op], '00:00:00');
                }
            }
            // For "end" operators, use end of day
            foreach (['lte', 'lt'] as $op) {
                if (isset($condition[$op])) {
                    $condition[$op] = $this->normalizeDate($condition[$op], '23:59:59');
                }
            }
        }

        $this->apply($select, $field, $condition);
    }

    /**
     * Decode JSON string to array if needed
     *
     * Handles edge case where condition is passed as JSON string
     * (e.g., '{"eq": "pending"}' instead of ['eq' => 'pending'])
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
        switch ($operator) {
            case 'eq':
                $select->where("{$field} = ?", $value);
                break;

            case 'neq':
                $select->where("{$field} != ?", $value);
                break;

            case 'in':
                if (is_array($value) && !empty($value)) {
                    $select->where("{$field} IN (?)", $value);
                }
                break;

            case 'nin':
            case 'not_in':
                if (is_array($value) && !empty($value)) {
                    $select->where("{$field} NOT IN (?)", $value);
                }
                break;

            case 'like':
                $select->where("{$field} LIKE ?", $value);
                break;

            case 'nlike':
            case 'not_like':
                $select->where("{$field} NOT LIKE ?", $value);
                break;

            case 'gt':
                $select->where("{$field} > ?", $value);
                break;

            case 'gte':
                $select->where("{$field} >= ?", $value);
                break;

            case 'lt':
                $select->where("{$field} < ?", $value);
                break;

            case 'lte':
                $select->where("{$field} <= ?", $value);
                break;

            case 'null':
                if ($value) {
                    $select->where("{$field} IS NULL");
                } else {
                    $select->where("{$field} IS NOT NULL");
                }
                break;
        }
    }
}
