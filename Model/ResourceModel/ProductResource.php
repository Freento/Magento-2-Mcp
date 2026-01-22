<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel;

use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Magento\Framework\DB\Select;

class ProductResource extends AbstractResource
{
    private const PRODUCT_ENTITY_TYPE_ID = 4;

    protected function applyCustomFilters(Select $select, Schema $schema, array $arguments, array &$appliedFilters): void
    {
        $connection = $this->resourceConnection->getConnection();
        $storeId = (int)($arguments['store_id'] ?? 0);

        if (!empty($arguments['ids']) && is_array($arguments['ids'])) {
            $ids = array_map('intval', $arguments['ids']);
            $select->where('main_table.entity_id IN (?)', $ids);
            $appliedFilters[] = 'ids: ' . implode(',', $ids);
        }

        if (isset($arguments['status']) && $arguments['status'] !== '') {
            $this->applyEavAttributeFilter($select, $connection, 'status', (int)$arguments['status'], $storeId);
            $appliedFilters[] = "status: {$arguments['status']}";
        }

        if (isset($arguments['visibility']) && $arguments['visibility'] !== '') {
            $this->applyEavAttributeFilter($select, $connection, 'visibility', (int)$arguments['visibility'], $storeId);
            $appliedFilters[] = "visibility: {$arguments['visibility']}";
        }

        if (!empty($arguments['attribute_filter']) && is_array($arguments['attribute_filter'])) {
            foreach ($arguments['attribute_filter'] as $attrCode => $attrValue) {
                if ($this->applyEavAttributeFilter($select, $connection, $attrCode, $attrValue, $storeId)) {
                    $displayValue = is_string($attrValue) ? $attrValue : json_encode($attrValue);
                    $appliedFilters[] = "{$attrCode}: {$displayValue}";
                }
            }
        }
    }

    protected function beforeFetch(Select $select, Schema $schema, array $arguments): void
    {
        $select->distinct(true);
    }

    protected function postProcessRows(array $rows, Schema $schema, array $arguments): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $connection = $this->resourceConnection->getConnection();
        $storeId = (int)($arguments['store_id'] ?? 0);
        $entityIds = array_column($rows, 'entity_id');

        $names = $this->loadEavAttributeValues($connection, 'name', $entityIds, $storeId);
        $prices = $this->loadEavAttributeValues($connection, 'price', $entityIds, $storeId);

        foreach ($rows as &$product) {
            $entityId = $product['entity_id'];
            $product['name'] = $names[$entityId] ?? 'N/A';
            $product['price'] = $prices[$entityId] ?? null;
        }

        return $rows;
    }

    private function applyEavAttributeFilter(
        Select $select,
        $connection,
        string $attributeCode,
        mixed $value,
        int $storeId
    ): bool {
        $attribute = $this->getAttributeInfo($connection, $attributeCode);
        if (!$attribute) {
            return false;
        }

        $backendType = $attribute['backend_type'];
        if ($backendType === 'static') {
            $select->where("main_table.{$attributeCode} = ?", $value);
            return true;
        }

        $eavTable = $this->resourceConnection->getTableName("catalog_product_entity_{$backendType}");
        $alias = "eav_{$attributeCode}";

        $select->joinLeft(
            [$alias => $eavTable],
            "main_table.entity_id = {$alias}.entity_id" .
            " AND {$alias}.attribute_id = " . (int)$attribute['attribute_id'] .
            " AND {$alias}.store_id IN (0, {$storeId})",
            []
        );

        if (is_string($value) && strpos($value, '%') !== false) {
            $select->where("{$alias}.value LIKE ?", $value);
        } else {
            $select->where("{$alias}.value = ?", $value);
        }

        return true;
    }

    private function getAttributeInfo($connection, string $attributeCode): ?array
    {
        $eavAttributeTable = $this->resourceConnection->getTableName('eav_attribute');

        $select = $connection->select()
            ->from($eavAttributeTable, ['attribute_id', 'backend_type', 'frontend_input'])
            ->where('attribute_code = ?', $attributeCode)
            ->where('entity_type_id = ?', self::PRODUCT_ENTITY_TYPE_ID);

        $result = $connection->fetchRow($select);

        return $result ?: null;
    }

    private function loadEavAttributeValues($connection, string $attributeCode, array $entityIds, int $storeId): array
    {
        $attribute = $this->getAttributeInfo($connection, $attributeCode);
        if (!$attribute || $attribute['backend_type'] === 'static') {
            return [];
        }

        $eavTable = $this->resourceConnection->getTableName("catalog_product_entity_{$attribute['backend_type']}");

        $selectDefault = $connection->select()
            ->from($eavTable, ['entity_id', 'value'])
            ->where('attribute_id = ?', $attribute['attribute_id'])
            ->where('store_id = 0')
            ->where('entity_id IN (?)', $entityIds);

        $defaultValues = $connection->fetchPairs($selectDefault);

        if ($storeId > 0) {
            $selectStore = $connection->select()
                ->from($eavTable, ['entity_id', 'value'])
                ->where('attribute_id = ?', $attribute['attribute_id'])
                ->where('store_id = ?', $storeId)
                ->where('entity_id IN (?)', $entityIds);

            $storeValues = $connection->fetchPairs($selectStore);

            return array_replace($defaultValues, $storeValues);
        }

        return $defaultValues;
    }
}
