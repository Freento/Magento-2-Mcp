<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\ConditionApplier;
use Freento\Mcp\Model\EntityTool\ListResultFactory;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ProductTierPriceResource extends AbstractResource
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param ConditionApplier $conditionApplier
     * @param ListResultFactory $listResultFactory
     * @param DateTimeHelper $dateTimeHelper
     * @param LinkField $linkField
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ConditionApplier $conditionApplier,
        ListResultFactory $listResultFactory,
        DateTimeHelper $dateTimeHelper,
        private readonly LinkField $linkField
    ) {
        parent::__construct($resourceConnection, $conditionApplier, $listResultFactory, $dateTimeHelper);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    protected function applyRequiredJoins(Select $select, Schema $schema, bool $addJoinedFieldsToSelect = true): void
    {
        // The tier price table is keyed by the catalog link field.
        $linkField = $this->linkField->forProduct();
        $select->join(
            ['catalog_product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
            "main_table.$linkField = catalog_product_entity.$linkField",
            []
        );

        $select->joinLeft(
            ['customer_group' => $this->resourceConnection->getTableName('customer_group')],
            'main_table.customer_group_id = customer_group.customer_group_id',
            []
        );
    }
}
