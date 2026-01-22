<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\EntityTool;

/**
 * JSON Schema definitions for filter operators.
 *
 * Provides reusable operator schemas for MCP tool input definitions.
 * Used by AbstractTool::getInputSchema() to generate filter field schemas.
 *
 * Each field type supports different operators:
 * - string: eq, neq, in, nin, like, nlike
 * - numeric/integer: eq, neq, gt, gte, lt, lte, in, nin
 * - date: eq, neq, gt, gte, lt, lte
 *
 * Usage in custom tool:
 * ```php
 * public function getInputSchema(): array
 * {
 *     return [
 *         'type' => 'object',
 *         'properties' => [
 *             'status' => Operators::stringField('Order status'),
 *             'grand_total' => Operators::numericField('Order total'),
 *             'created_at' => Operators::dateField('Creation date'),
 *         ]
 *     ];
 * }
 * ```
 *
 * This allows LLM to use filters like:
 * - {"status": {"eq": "pending"}}
 * - {"grand_total": {"gte": 100, "lte": 500}}
 * - {"created_at": {"gte": "2024-01-01"}}
 */
class Operators
{
    /**
     * Base operators schema for string fields
     *
     * Operators: eq, neq, in, nin, like, nlike
     *
     * @return array JSON Schema definition
     */
    public static function string(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'eq' => ['type' => 'string', 'description' => 'Equals'],
                'neq' => ['type' => 'string', 'description' => 'Not equals'],
                'in' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'In list of values'
                ],
                'nin' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Not in list of values'
                ],
                'like' => ['type' => 'string', 'description' => 'SQL LIKE pattern (use % as wildcard)'],
                'nlike' => ['type' => 'string', 'description' => 'SQL NOT LIKE pattern']
            ]
        ];
    }

    /**
     * Base operators schema for numeric fields
     *
     * Operators: eq, neq, gt, gte, lt, lte, in, nin
     *
     * @return array JSON Schema definition
     */
    public static function numeric(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'eq' => ['type' => 'number', 'description' => 'Equals'],
                'neq' => ['type' => 'number', 'description' => 'Not equals'],
                'gt' => ['type' => 'number', 'description' => 'Greater than'],
                'gte' => ['type' => 'number', 'description' => 'Greater than or equal'],
                'lt' => ['type' => 'number', 'description' => 'Less than'],
                'lte' => ['type' => 'number', 'description' => 'Less than or equal'],
                'in' => [
                    'type' => 'array',
                    'items' => ['type' => 'number'],
                    'description' => 'In list of values'
                ],
                'nin' => [
                    'type' => 'array',
                    'items' => ['type' => 'number'],
                    'description' => 'Not in list of values'
                ]
            ]
        ];
    }

    /**
     * Base operators schema for date fields
     *
     * Operators: eq, neq, gt, gte, lt, lte
     * Dates should be in format: YYYY-MM-DD or YYYY-MM-DD HH:MM:SS
     *
     * @return array JSON Schema definition
     */
    public static function date(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'eq' => ['type' => 'string', 'description' => 'Equals (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)'],
                'neq' => ['type' => 'string', 'description' => 'Not equals'],
                'gt' => ['type' => 'string', 'description' => 'After date'],
                'gte' => ['type' => 'string', 'description' => 'On or after date'],
                'lt' => ['type' => 'string', 'description' => 'Before date'],
                'lte' => ['type' => 'string', 'description' => 'On or before date']
            ]
        ];
    }

    /**
     * String field schema with description
     *
     * @param string $description Field description for LLM
     * @return array JSON Schema with operators
     */
    public static function stringField(string $description = ''): array
    {
        $result = self::string();
        if ($description) {
            $result['description'] = $description;
        }
        return $result;
    }

    /**
     * Integer field schema with description
     *
     * Uses numeric operators (eq, neq, gt, gte, lt, lte, in, nin)
     *
     * @param string $description Field description for LLM
     * @return array JSON Schema with operators
     */
    public static function integerField(string $description = ''): array
    {
        $result = self::numeric();
        if ($description) {
            $result['description'] = $description;
        }
        return $result;
    }

    /**
     * Numeric (float/decimal) field schema with description
     *
     * Uses numeric operators (eq, neq, gt, gte, lt, lte, in, nin)
     *
     * @param string $description Field description for LLM
     * @return array JSON Schema with operators
     */
    public static function numericField(string $description = ''): array
    {
        $result = self::numeric();
        if ($description) {
            $result['description'] = $description;
        }
        return $result;
    }

    /**
     * Date field schema with description
     *
     * Uses date operators (eq, neq, gt, gte, lt, lte)
     *
     * @param string $description Field description for LLM
     * @return array JSON Schema with operators
     */
    public static function dateField(string $description = ''): array
    {
        $result = self::date();
        if ($description) {
            $result['description'] = $description;
        }
        return $result;
    }
}
