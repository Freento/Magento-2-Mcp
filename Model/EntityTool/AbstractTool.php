<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\EntityTool;

use Freento\Mcp\Api\ToolInterface;
use Freento\Mcp\Api\ToolResultInterface;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ToolResultFactory;

/**
 * Abstract base class for entity list MCP tools.
 *
 * Provides a standardized way to create tools that list and filter Magento entities.
 * Handles the MCP protocol layer (schema generation, output formatting) while
 * delegating database operations to a Resource Model.
 *
 * ## Creating a Tool
 *
 * 1. Extend this class
 * 2. Implement buildSchema() to define entity structure
 * 3. Implement getResource() to return the resource model
 * 4. Create a corresponding Resource class extending AbstractResource
 *
 * ```php
 * class GetOrders extends AbstractTool
 * {
 *     public function __construct(
 *         OrderResource $orderResource,
 *         ToolResultFactory $resultFactory,
 *         StringHelper $stringHelper
 *     ) {
 *         parent::__construct($resultFactory, $stringHelper);
 *         $this->orderResource = $orderResource;
 *     }
 *
 *     protected function buildSchema(): Schema
 *     {
 *         return new Schema(
 *             entity: 'order',
 *             table: 'sales_order',
 *             fields: [
 *                 new Field(name: 'entity_id', type: 'integer'),
 *                 new Field(name: 'status', type: 'string'),
 *                 new Field(name: 'grand_total', type: 'currency'),
 *             ]
 *         );
 *     }
 *
 *     protected function getResource(): AbstractResource
 *     {
 *         return $this->orderResource;
 *     }
 * }
 * ```
 *
 * ## Optional Overrides
 *
 * - getOutputFields(): Customize which fields appear in output
 * - transformRows(): Transform values before output (e.g., status codes → labels)
 * - getExtraSchemaProperties(): Add custom filter parameters
 * - getDescriptionLines(): Add lines to tool description
 * - getExamplePrompts(): Customize example prompts in description
 * - getName(): Override default 'get_{entities}' naming
 */
abstract class AbstractTool implements ToolInterface
{
    protected ToolResultFactory $resultFactory;
    protected StringHelper $stringHelper;

    /** @var Schema|null Cached schema instance */
    private ?Schema $schema = null;

    public function __construct(
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper
    ) {
        $this->resultFactory = $resultFactory;
        $this->stringHelper = $stringHelper;
    }

    /**
     * Build entity schema definition
     *
     * Must return a Schema with entity name, table, and field definitions.
     * Called once and cached for the tool lifetime.
     *
     * @return Schema Entity schema
     */
    abstract protected function buildSchema(): Schema;

    /**
     * Get resource model for database operations
     *
     * The resource handles query building, filtering, and data fetching.
     * Should return an instance of AbstractResource subclass.
     *
     * @return AbstractResource Resource model instance
     */
    abstract protected function getResource(): AbstractResource;

    /**
     * Get cached schema instance
     *
     * Lazily builds and caches the schema on first access.
     */
    protected function getSchema(): Schema
    {
        if ($this->schema === null) {
            $this->schema = $this->buildSchema();
        }
        return $this->schema;
    }

    /**
     * Get tool name for MCP registration
     *
     * Default: 'get_{entities}' (e.g., 'get_orders', 'get_products')
     * Override to customize the tool name.
     */
    public function getName(): string
    {
        return 'get_' . $this->stringHelper->pluralize($this->getSchema()->getEntity());
    }

    /**
     * Additional description lines for tool documentation
     *
     * Override to add entity-specific description lines.
     * Each line is prefixed with "- " in the output.
     *
     * @return string[] Additional description lines
     */
    protected function getDescriptionLines(): array
    {
        return [];
    }

    /**
     * Example prompts for tool documentation
     *
     * Override to provide entity-specific example prompts.
     * Helps LLM understand when to use this tool.
     *
     * @return string[] Example prompts
     */
    protected function getExamplePrompts(): array
    {
        $entity = $this->getSchema()->getEntity();
        $entities = $this->stringHelper->pluralize($entity);
        return [
            "Show me recent {$entities}",
            "List all {$entities}",
        ];
    }

    /**
     * Generate tool description for LLM
     *
     * Combines schema info, description lines, and example prompts
     * into a comprehensive tool description.
     */
    public function getDescription(): string
    {
        $schema = $this->getSchema();
        $entity = $schema->getEntity();
        $entities = $this->stringHelper->pluralize($entity);
        $limit = $schema->getDefaultLimit();
        $maxLimit = $schema->getMaxLimit();

        $lines = [
            "Get list of {$entities} from Magento store with optional filters.",
            "",
            "Use this tool when you need to:",
            "- Retrieve {$entity} details",
            "- Search for specific {$entities}",
            "- Analyze {$entity} data",
        ];

        // Add entity-specific description lines
        foreach ($this->getDescriptionLines() as $line) {
            $lines[] = "- {$line}";
        }

        $lines[] = "";
        $lines[] = "All filter parameters are optional. Returns {$entity} list with pagination.";
        $lines[] = "Default limit is {$limit} {$entities}, maximum is {$maxLimit}.";
        $lines[] = "";
        $lines[] = "Example prompts:";

        foreach ($this->getExamplePrompts() as $prompt) {
            $lines[] = "- \"{$prompt}\"";
        }

        return implode("\n", $lines);
    }

    /**
     * Additional input schema properties
     *
     * Override to add custom filter parameters not derived from schema fields.
     * Useful for special filters like 'ids' array or boolean flags.
     *
     * Example:
     * ```php
     * protected function getExtraSchemaProperties(): array
     * {
     *     return [
     *         'ids' => [
     *             'type' => 'array',
     *             'items' => ['type' => 'integer'],
     *             'description' => 'Filter by multiple IDs',
     *         ],
     *         'low_stock' => [
     *             'type' => 'boolean',
     *             'description' => 'Show only low stock items',
     *         ],
     *     ];
     * }
     * ```
     *
     * @return array Additional JSON Schema properties
     */
    protected function getExtraSchemaProperties(): array
    {
        return [];
    }

    /**
     * Transform rows before output formatting
     *
     * Override to convert values for display (e.g., status codes to labels).
     * Called after data is fetched from database, before formatting.
     *
     * Example:
     * ```php
     * protected function transformRows(array $rows): array
     * {
     *     foreach ($rows as &$row) {
     *         if (isset($row['is_active'])) {
     *             $row['is_active'] = $row['is_active'] ? 'Active' : 'Inactive';
     *         }
     *     }
     *     return $rows;
     * }
     * ```
     *
     * @param array $rows Raw database rows
     * @return array Transformed rows
     */
    protected function transformRows(array $rows): array
    {
        return $rows;
    }

    /**
     * Get list of field names to include in output
     *
     * Override to customize which fields appear in the tool output.
     * By default, outputs all fields defined in the schema.
     *
     * Example:
     * ```php
     * protected function getOutputFields(): array
     * {
     *     return ['entity_id', 'sku', 'name', 'price', 'status'];
     * }
     * ```
     *
     * @return string[] Field names to output
     */
    protected function getOutputFields(): array
    {
        return array_map(
            fn(Field $field) => $field->getName(),
            $this->getSchema()->getFields()
        );
    }

    /**
     * Generate MCP input schema
     *
     * Builds JSON Schema for tool parameters based on:
     * - Aggregation parameters (function, field, group_by) - optional
     * - filters: Object with filterable fields and extra filter properties
     * - Standard pagination parameters (limit, offset, sort_by, sort_dir)
     */
    public function getInputSchema(): array
    {
        $schema = $this->getSchema();
        $properties = [];
        $filterProperties = [];

        // Add aggregation parameters if schema has aggregate/groupBy fields
        $aggFields = $schema->getAggregateFields();
        $groupByOptions = $schema->getGroupByOptions();

        if (!empty($aggFields) || !empty($groupByOptions)) {
            $properties['function'] = [
                'type' => 'string',
                'enum' => ['sum', 'count', 'avg', 'min', 'max'],
                'description' => 'Aggregation function (if provided, returns aggregated data instead of list)'
            ];

            if (!empty($aggFields)) {
                $aggFieldNames = array_values(array_map(fn($f) => $f->getName(), $aggFields));
                $properties['field'] = [
                    'type' => 'string',
                    'enum' => $aggFieldNames,
                    'description' => 'Field to aggregate (required for sum/avg/min/max)'
                ];
            }

            if (!empty($groupByOptions)) {
                $properties['group_by'] = [
                    'type' => 'string',
                    'enum' => $groupByOptions,
                    'description' => 'Group results by field or time period'
                ];
            }
        }

        // Generate filter properties for each filterable field
        foreach ($schema->getFilterableFields() as $field) {
            $type = $field->getType();
            $description = $field->getDescription() ?? ucfirst(str_replace('_', ' ', $field->getName()));

            // Get operators schema based on field type
            $filterProperties[$field->getName()] = $this->getOperatorsForType($type, $description);
        }

        // Add extra properties defined by child class to filters
        foreach ($this->getExtraSchemaProperties() as $name => $prop) {
            $filterProperties[$name] = $prop;
        }

        // Add filters object
        $properties['filters'] = [
            'type' => 'object',
            'description' => 'Filter conditions for the query',
            'properties' => $filterProperties,
        ];

        // Add pagination parameters
        $properties['limit'] = [
            'type' => 'integer',
            'description' => "Maximum items to return (default: {$schema->getDefaultLimit()}, max: {$schema->getMaxLimit()})",
            'default' => $schema->getDefaultLimit(),
            'maximum' => $schema->getMaxLimit()
        ];

        $properties['offset'] = [
            'type' => 'integer',
            'description' => 'Number of items to skip for pagination',
            'default' => 0
        ];

        // Add sorting parameters if sortable fields exist
        $sortFields = $schema->getSortableFieldNames();
        if (!empty($sortFields)) {
            $properties['sort_by'] = [
                'type' => 'string',
                'enum' => $sortFields,
                'description' => 'Field to sort by',
                'default' => $sortFields[0] ?? 'created_at'
            ];

            $properties['sort_dir'] = [
                'type' => 'string',
                'enum' => ['asc', 'desc'],
                'description' => 'Sort direction',
                'default' => 'desc'
            ];
        }

        return [
            'type' => 'object',
            'properties' => $properties,
        ];
    }

    /**
     * Execute tool and return formatted result
     *
     * Main entry point called by MCP server:
     * 1. Extracts filter and pagination parameters from arguments
     * 2. Delegates to resource model for data fetching
     * 3. Transforms rows (if overridden) - only in list mode
     * 4. Formats output as human-readable text
     *
     * Supports two modes:
     * - List mode (default): Returns entity records
     * - Aggregate mode (when 'function' is provided): Returns aggregated data
     *
     * @param array $arguments Tool arguments from MCP request
     * @return ToolResultInterface Formatted result
     */
    public function execute(array $arguments): ToolResultInterface
    {
        $schema = $this->getSchema();
        $resource = $this->getResource();

        // Extract filters from nested 'filters' key
        $filters = (array)($arguments['filters'] ?? []);

        $limit = (int)($arguments['limit'] ?? 0);
        $offset = max(0, (int)($arguments['offset'] ?? 0));
        $sortBy = (string)($arguments['sort_by'] ?? '');
        $sortDir = (string)($arguments['sort_dir'] ?? 'DESC');

        // Check for aggregation mode
        $function = (string)($arguments['function'] ?? '');
        $field = (string)($arguments['field'] ?? '');
        $groupBy = (string)($arguments['group_by'] ?? '');

        // Fetch data from database via resource model
        $result = $resource->getList(
            $schema,
            $filters,
            $limit,
            $offset,
            $sortBy,
            $sortDir,
            $function,
            $field,
            $groupBy
        );

        // Format output based on mode
        if ($function !== '') {
            // Aggregate mode
            $formatted = $this->formatAggregateOutput(
                $result->getRows(),
                $function,
                $field,
                $groupBy,
                $result->getAppliedFilters()
            );
        } else {
            // List mode
            $rows = $this->transformRows($result->getRows());
            $formatted = $this->formatOutput($rows, $result->getAppliedFilters(), $offset);
        }

        return $this->resultFactory->createText($formatted);
    }

    /**
     * Format aggregate results as simple text output
     *
     * @param array $rows Aggregate result rows
     * @param string $function Aggregate function used
     * @param string $field Field aggregated
     * @param string $groupBy Group by option
     * @param array $appliedFilters Applied filter descriptions
     * @return string Formatted output
     */
    protected function formatAggregateOutput(
        array $rows,
        string $function,
        string $field,
        string $groupBy,
        array $appliedFilters
    ): string {
        $entity = $this->getSchema()->getEntity();
        $function = strtoupper($function);

        // Build description
        if ($function === 'COUNT') {
            $description = ucfirst($entity) . ' count';
        } else {
            $fieldLabel = $field ? str_replace('_', ' ', $field) : 'value';
            $description = ucfirst(strtolower($function)) . " of {$fieldLabel}";
        }

        $lines = [];

        if ($groupBy) {
            $groupByLabel = str_replace('_', ' ', $groupBy);
            $lines[] = "{$description} by {$groupByLabel}:";
            $lines[] = '';

            if (empty($rows)) {
                $lines[] = 'No data found.';
            } else {
                foreach ($rows as $row) {
                    $key = $row['group_key'] ?? 'Unknown';
                    $value = $this->formatAggregateValue($row['value'], $function, $field);
                    $lines[] = "  {$key}: {$value}";
                }
            }
        } else {
            $value = $rows[0]['value'] ?? 0;
            $formattedValue = $this->formatAggregateValue($value, $function, $field);
            $lines[] = "{$description}: {$formattedValue}";
        }

        if (!empty($appliedFilters)) {
            $lines[] = '';
            $lines[] = 'Filters: ' . implode(', ', $appliedFilters);
        }

        return implode("\n", $lines);
    }

    /**
     * Format aggregate value based on function and field type
     *
     * @param mixed $value Raw value
     * @param string $function Aggregate function
     * @param string $field Field name
     * @return string Formatted value
     */
    protected function formatAggregateValue($value, string $function, string $field): string
    {
        if ($function === 'COUNT') {
            return number_format((int)$value);
        }

        $fieldDef = $this->getSchema()->getField($field);
        if ($fieldDef && $fieldDef->getType() === 'currency') {
            return number_format((float)$value, 2);
        }

        $floatValue = (float)$value;
        if (floor($floatValue) == $floatValue) {
            return number_format((int)$floatValue);
        }

        return number_format($floatValue, 1);
    }

    /**
     * Format rows as human-readable key-value output
     *
     * Generates output like:
     * ```
     * Found 2 orders:
     *
     * Order 1:
     *   entity_id: 1
     *   status: pending
     *   grand_total: 99.99
     *
     * Order 2:
     *   entity_id: 2
     *   status: complete
     *   grand_total: 149.99
     *
     * Filters: status: pending
     * ```
     *
     * @param array $rows Transformed data rows
     * @param array $appliedFilters List of applied filter descriptions
     * @param int $offset Pagination offset (for numbering)
     * @return string Formatted output text
     */
    protected function formatOutput(array $rows, array $appliedFilters, int $offset): string
    {
        $schema = $this->getSchema();
        $entity = $schema->getEntity();
        $entities = $this->stringHelper->pluralize($entity);
        $count = count($rows);

        // Handle empty result
        if ($count === 0) {
            $result = "No {$entities} found.";
            if (!empty($appliedFilters)) {
                $result .= "\nFilters: " . implode(', ', $appliedFilters);
            }
            return $result;
        }

        $lines = [];

        // Header: "Found X orders:"
        $entityLabel = $count === 1 ? $entity : $entities;
        $header = "Found {$count} {$entityLabel}";
        if ($offset > 0) {
            $header .= " (offset: {$offset})";
        }
        $lines[] = $header . ':';
        $lines[] = '';

        // Output each row with only the fields specified in getOutputFields()
        $outputFields = $this->getOutputFields();
        foreach ($rows as $index => $row) {
            $lines[] = ucfirst($entity) . ' ' . ($index + 1 + $offset) . ':';

            foreach ($outputFields as $fieldName) {
                // Skip fields not present in row data
                if (!array_key_exists($fieldName, $row)) {
                    continue;
                }

                $field = $schema->getField($fieldName);
                $formattedValue = $this->formatValue($row[$fieldName], $field);
                $lines[] = "  {$fieldName}: {$formattedValue}";
            }
            $lines[] = '';
        }

        // Append filter summary
        if (!empty($appliedFilters)) {
            $lines[] = 'Filters: ' . implode(', ', $appliedFilters);
        }

        return implode("\n", $lines);
    }

    /**
     * Format value based on field type
     *
     * Applies type-specific formatting:
     * - currency: 2 decimal places (e.g., 99.99)
     * - numeric/float: float cast
     * - integer: int cast
     * - string/other: as-is
     *
     * Note: If value was already transformed to a non-numeric string
     * by transformRows() (e.g., "Active"), it's returned as-is to
     * prevent conversion back to 0.
     *
     * @param mixed $value Raw value
     * @param Field|null $field Field definition (for type info)
     * @return string Formatted value
     */
    protected function formatValue(mixed $value, ?Field $field): string
    {
        if ($value === null) {
            return '';
        }

        // If value is not numeric, return as string (preserves transformed values)
        if ($field === null || !is_numeric($value)) {
            return (string)$value;
        }

        // Apply type-specific formatting for numeric values
        return match ($field->getType()) {
            'currency' => number_format((float)$value, 2),
            'numeric', 'float' => (string)(float)$value,
            'integer', 'int' => (string)(int)$value,
            default => (string)$value,
        };
    }

    /**
     * Get JSON Schema operators definition for field type
     *
     * Maps field types to appropriate operator schemas:
     * - numeric/currency/float → numeric operators (gt, gte, lt, lte, etc.)
     * - integer → integer operators
     * - date → date operators
     * - string (default) → string operators (eq, like, in, etc.)
     *
     * @param string $type Field type
     * @param string $description Field description
     * @return array JSON Schema definition with operators
     */
    protected function getOperatorsForType(string $type, string $description): array
    {
        return match ($type) {
            'numeric', 'currency', 'float' => Operators::numericField($description),
            'integer', 'int' => Operators::integerField($description),
            'date', 'datetime' => Operators::dateField($description),
            default => Operators::stringField($description),
        };
    }
}
