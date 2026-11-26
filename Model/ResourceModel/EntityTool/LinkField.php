<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Resolves the link field for catalog entities (products, categories, catalog price rules).
 */
class LinkField
{
    /**
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        private readonly MetadataPool $metadataPool
    ) {
    }

    /**
     * Get the product link field
     *
     * @return string
     * @throws \Exception
     */
    public function forProduct(): string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * Get the category link field
     *
     * @return string
     * @throws \Exception
     */
    public function forCategory(): string
    {
        return $this->metadataPool->getMetadata(CategoryInterface::class)->getLinkField();
    }

    /**
     * Get the catalog price rule link field
     *
     * @return string
     * @throws \Exception
     */
    public function forCatalogRule(): string
    {
        return $this->metadataPool->getMetadata(RuleInterface::class)->getLinkField();
    }
}
