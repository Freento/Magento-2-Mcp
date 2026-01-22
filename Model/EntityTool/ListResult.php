<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\EntityTool;

/**
 * Container for list query results.
 *
 * Holds query result rows and information about applied filters.
 * Returned by AbstractResource::getList() and used by AbstractTool for formatting.
 *
 * Usage (via factory in Resource classes):
 * ```php
 * return $this->listResultFactory->create([
 *     'rows' => [
 *         ['id' => 1, 'name' => 'Product A'],
 *         ['id' => 2, 'name' => 'Product B'],
 *     ],
 *     'appliedFilters' => ['status = enabled', 'store_id = 1']
 * ]);
 * ```
 *
 * Reading results:
 * ```php
 * foreach ($result->getRows() as $row) {
 *     // Process row
 * }
 * ```
 */
class ListResult
{
    /**
     * @param array<int, array<string, mixed>> $rows Query result rows
     * @param string[] $appliedFilters Human-readable filter descriptions
     */
    public function __construct(
        private array $rows,
        private array $appliedFilters = []
    ) {
    }

    /**
     * Get result rows
     *
     * @return array<int, array<string, mixed>> Array of associative arrays (field => value)
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Get list of applied filters for output
     *
     * @return string[] Human-readable filter descriptions (e.g., "status = pending")
     */
    public function getAppliedFilters(): array
    {
        return $this->appliedFilters;
    }

    /**
     * Check if result is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->rows);
    }

    /**
     * Get number of rows
     */
    public function count(): int
    {
        return count($this->rows);
    }
}
