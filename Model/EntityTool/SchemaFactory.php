<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\EntityTool;

use Freento\Mcp\Model\Config;

/**
 * Factory for creating Schema instances with anonymity mode support.
 *
 * Centralizes the anonymity config reading so individual tools
 * don't need to know about privacy settings.
 */
class SchemaFactory
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Create a Schema instance with anonymity mode applied from config
     *
     * @param string $entity Entity name
     * @param string $table Database table name
     * @param Field[] $fields List of field definitions
     * @param string $tableAlias SQL alias for main table
     * @param int $defaultLimit Default pagination limit
     * @param int $maxLimit Maximum allowed limit
     */
    public function create(
        string $entity,
        string $table,
        array $fields,
        string $tableAlias = 'main_table',
        int $defaultLimit = 50,
        int $maxLimit = 200
    ): Schema {
        return new Schema(
            entity: $entity,
            table: $table,
            fields: $fields,
            tableAlias: $tableAlias,
            defaultLimit: $defaultLimit,
            maxLimit: $maxLimit,
            anonymityEnabled: $this->config->isAnonymityEnabled()
        );
    }
}
