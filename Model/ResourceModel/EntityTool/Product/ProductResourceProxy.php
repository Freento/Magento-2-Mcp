<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool\Product;

use Freento\Mcp\Model\EntityTool\ConditionApplier;
use Freento\Mcp\Model\EntityTool\ListResult;
use Freento\Mcp\Model\EntityTool\ListResultFactory;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Magento\Framework\App\ResourceConnection;

/**
 * Product resource proxy
 *
 * Delegates to StoreProductResource when store_id filter is present,
 * otherwise delegates to ProductResource.
 */
class ProductResourceProxy extends AbstractResource
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param ConditionApplier $conditionApplier
     * @param ListResultFactory $listResultFactory
     * @param DateTimeHelper $dateTimeHelper
     * @param ProductResource $productResource
     * @param StoreProductResource $storeProductResource
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ConditionApplier $conditionApplier,
        ListResultFactory $listResultFactory,
        DateTimeHelper $dateTimeHelper,
        private readonly ProductResource $productResource,
        private readonly StoreProductResource $storeProductResource
    ) {
        parent::__construct($resourceConnection, $conditionApplier, $listResultFactory, $dateTimeHelper);
    }

    /**
     * @inheritDoc
     */
    public function getList(
        Schema $schema,
        array $filters = [],
        int $limit = 0,
        int $offset = 0,
        string $sortBy = '',
        string $sortDir = 'DESC',
        array $aggregations = [],
        array $groupBy = []
    ): ListResult {
        $resource = isset($filters['store_id']) && (int)$filters['store_id'] > 0
            ? $this->storeProductResource
            : $this->productResource;

        return $resource->getList(
            $schema,
            $filters,
            $limit,
            $offset,
            $sortBy,
            $sortDir,
            $aggregations,
            $groupBy
        );
    }
}
