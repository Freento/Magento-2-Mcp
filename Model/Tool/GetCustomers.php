<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\ResourceModel\CustomerResource;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\ToolResultFactory;

class GetCustomers extends AbstractTool
{
    private CustomerResource $customerResource;

    public function __construct(
        CustomerResource $customerResource,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper
    ) {
        parent::__construct($resultFactory, $stringHelper);
        $this->customerResource = $customerResource;
    }

    protected function getResource(): AbstractResource
    {
        return $this->customerResource;
    }

    protected function buildSchema(): Schema
    {
        return new Schema(
            entity: 'customer',
            table: 'customer_entity',
            fields: [
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    description: 'Customer entity ID'
                ),
                new Field(
                    name: 'email',
                    type: 'string',
                    description: 'Customer email address (supports wildcards: %@example.com)'
                ),
                new Field(
                    name: 'firstname',
                    type: 'string',
                    column: true
                ),
                new Field(
                    name: 'lastname',
                    type: 'string',
                    column: true
                ),
                new Field(
                    name: 'group_id',
                    type: 'integer',
                    sortable: false,
                    description: 'Customer group ID'
                ),
                new Field(
                    name: 'group_name',
                    column: 'customer_group.customer_group_code',
                    sortable: false
                ),
                new Field(
                    name: 'website_id',
                    type: 'integer',
                    description: 'Website ID'
                ),
                new Field(
                    name: 'store_id',
                    type: 'integer',
                    description: 'Store ID'
                ),
                new Field(
                    name: 'is_active',
                    type: 'integer',
                    sortable: false,
                    description: 'Active status (1 = active, 0 = inactive)'
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    description: 'Registration date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'updated_at',
                    type: 'date',
                    description: 'Last update date (YYYY-MM-DD)'
                ),
            ],
            defaultLimit: 50,
            maxLimit: 200
        );
    }

    protected function getDescriptionLines(): array
    {
        return [
            'Search for customers by email or ID',
            'Filter customers by registration date',
            'Analyze customer data',
        ];
    }

    protected function getExamplePrompts(): array
    {
        return [
            'Show me recent customers',
            'Find customer with email john@example.com',
            'Get customers registered last week',
            'List customers in group ID 1',
        ];
    }

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
