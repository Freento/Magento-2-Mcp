<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\AdminResource;
use Freento\Mcp\Model\ToolResultFactory;
use Freento\Mcp\Model\EntityTool\SchemaFactory;

class GetAdmins extends AbstractTool
{
    /**
     * @param AdminResource $adminResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly AdminResource $adminResource,
        private readonly SchemaFactory $schemaFactory,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper,
        DateTimeHelper $dateTimeHelper
    ) {
        parent::__construct($resultFactory, $stringHelper, $dateTimeHelper);
    }

    /**
     * @inheritDoc
     */
    protected function getResource(): AbstractResource
    {
        return $this->adminResource;
    }

    /**
     * @inheritDoc
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'admin',
            table: 'admin_user',
            fields: [
                new Field(
                    name: 'user_id',
                    type: 'integer',
                    description: 'Admin user ID'
                ),
                new Field(
                    name: 'username',
                    type: 'string',
                    description: 'Username. Supports wildcards.',
                    anonymous: true
                ),
                new Field(
                    name: 'email',
                    type: 'string',
                    description: 'Email. Supports wildcards: "%@example.com" (domain), "admin%" (starts with)',
                    anonymous: true
                ),
                new Field(
                    name: 'firstname',
                    type: 'string',
                    column: true,
                    anonymous: true
                ),
                new Field(
                    name: 'lastname',
                    type: 'string',
                    column: true,
                    anonymous: true
                ),
                new Field(
                    name: 'role_name',
                    type: 'string',
                    column: 'role.role_name',
                    sortable: false,
                    allowGroupBy: true,
                    description: 'Role name. Supports wildcards.'
                ),
                new Field(
                    name: 'is_active',
                    type: 'integer',
                    sortable: false,
                    allowGroupBy: true,
                    description: 'Active status (1 = active, 0 = inactive)'
                ),
                new Field(
                    name: 'created',
                    type: 'date',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day'],
                    description: 'Creation date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'modified',
                    type: 'date',
                    description: 'Last modified date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'logdate',
                    type: 'date',
                    sortable: false
                ),
                new Field(
                    name: 'lognum',
                    type: 'integer',
                    sortable: false,
                ),
            ],
            tableAlias: 'admin',
            defaultLimit: 20,
            maxLimit: 100
        );
    }

    /**
     * @inheritDoc
     */
    protected function getDescriptionLines(): array
    {
        return [
            'List all admin users',
            'Check admin roles and permissions',
            'Find admin by email or username',
            'Audit admin accounts',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me all admin users',
            'List active admins',
            'Find admin with email admin@example.com',
            'Who are the administrators?',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function transformRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (isset($row['is_active'])) {
                $row['is_active'] = $row['is_active'] ? 'Active' : 'Inactive';
            }
        }
        return $rows;
    }
}
