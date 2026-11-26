<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\EntityTool\SchemaFactory;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\CustomerAddressResource;
use Freento\Mcp\Model\ToolResultFactory;

class GetCustomerAddresses extends AbstractTool
{
    private const FLAG_FIELDS = ['is_active', 'is_default_billing', 'is_default_shipping'];

    /**
     * @param CustomerAddressResource $customerAddressResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly CustomerAddressResource $customerAddressResource,
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
        return $this->customerAddressResource;
    }

    /**
     * @inheritDoc
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'customer_address',
            table: 'customer_address_entity',
            fields: [
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    description: 'Address entity ID'
                ),
                new Field(
                    name: 'customer_id',
                    type: 'integer',
                    column: 'main_table.parent_id',
                    sortable: false,
                    description: 'Customer entity ID'
                ),
                new Field(
                    name: 'customer_email',
                    type: 'string',
                    column: 'customer.email',
                    sortable: false,
                    description: 'Customer email address (supports wildcards: %@example.com)',
                    anonymous: true
                ),
                new Field(
                    name: 'website_id',
                    type: 'integer',
                    column: 'customer.website_id',
                    sortable: false,
                    allowGroupBy: true,
                    description: 'Customer website ID'
                ),
                new Field(
                    name: 'is_default_billing',
                    type: 'integer',
                    column: 'IF(customer.default_billing = main_table.entity_id, 1, 0)',
                    sortable: false,
                    description: 'Default billing address of the customer (1 = yes, 0 = no)'
                ),
                new Field(
                    name: 'is_default_shipping',
                    type: 'integer',
                    column: 'IF(customer.default_shipping = main_table.entity_id, 1, 0)',
                    sortable: false,
                    description: 'Default shipping address of the customer (1 = yes, 0 = no)'
                ),
                new Field(
                    name: 'firstname',
                    type: 'string',
                    anonymous: true
                ),
                new Field(
                    name: 'lastname',
                    type: 'string',
                    anonymous: true
                ),
                new Field(
                    name: 'company',
                    type: 'string',
                    sortable: false,
                    anonymous: true
                ),
                new Field(
                    name: 'street',
                    type: 'string',
                    sortable: false,
                    description: 'Street address (supports wildcards)',
                    anonymous: true
                ),
                new Field(
                    name: 'city',
                    type: 'string',
                    allowGroupBy: true
                ),
                new Field(
                    name: 'region',
                    type: 'string',
                    allowGroupBy: true,
                    description: 'State/province name'
                ),
                new Field(
                    name: 'postcode',
                    type: 'string',
                    sortable: false,
                    anonymous: true
                ),
                new Field(
                    name: 'country_id',
                    type: 'string',
                    allowGroupBy: true,
                    description: 'Country code (ISO 3166-1 alpha-2, e.g., US)'
                ),
                new Field(
                    name: 'telephone',
                    type: 'string',
                    sortable: false,
                    anonymous: true
                ),
                new Field(
                    name: 'vat_id',
                    type: 'string',
                    sortable: false,
                    description: 'VAT number',
                    anonymous: true
                ),
                new Field(
                    name: 'is_active',
                    type: 'integer',
                    sortable: false,
                    allowGroupBy: true,
                    description: 'Active status (1 = active, 0 = inactive)'
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day'],
                    description: 'Creation date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'updated_at',
                    type: 'date'
                ),
            ],
            defaultLimit: 50,
            maxLimit: 200
        );
    }

    /**
     * @inheritDoc
     */
    protected function getDescriptionLines(): array
    {
        return [
            'Retrieve customer address book entries',
            'Find addresses of a specific customer by customer ID or email',
            'Identify default billing and shipping addresses',
            'Analyze customer geography by country, region or city',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show all addresses of customer ID 42',
            'What is the default shipping address of john@example.com?',
            'How many customer addresses are there per country?',
            'List customer addresses in New York',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function transformRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (isset($row['street'])) {
                $row['street'] = str_replace("\n", ', ', $row['street']);
            }

            foreach (self::FLAG_FIELDS as $flagField) {
                if (isset($row[$flagField])) {
                    $row[$flagField] = $row[$flagField] ? 'Yes' : 'No';
                }
            }
        }

        return $rows;
    }
}
