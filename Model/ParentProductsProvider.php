<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MGH\ParentProducts\Model;

use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use MGH\ParentProducts\Api\ParentProductsProviderInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Backend\Model\UrlInterface;

class ParentProductsProvider implements ParentProductsProviderInterface
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly UrlInterface $backendUrl
    ) {
    }

    /**
     * @param int $productId
     * @return array[] Each row: [id, sku, name, type, link_type, edit_url]
     */
    public function getParentProducts(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $relationTable = $this->resource->getTableName('catalog_product_relation');
        $superLinkTable = $this->resource->getTableName('catalog_product_super_link');

        $parentIds = $connection->fetchCol(
            $connection->select()->from($relationTable, ['parent_id'])->where('child_id = ?', $productId)
        );

        if (empty($parentIds)) {
            $parentIds = $connection->fetchCol(
                $connection->select()->from($superLinkTable, ['parent_id'])->where('product_id = ?', $productId)
            );
        }

        $parentIds = array_values(array_unique(array_map('intval', $parentIds)));

        if (empty($parentIds)) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'sku', 'type_id']);
        $collection->addFieldToFilter('entity_id', ['in' => $parentIds]);

        $rows = [];
        foreach ($collection as $parent) {
            $type = (string)$parent->getTypeId();
            $name = (string)$parent->getName();
            if ($name === '') {
                $name = (string)$parent->getSku(); // fallback
            }
            $rows[] = [
                'id' => (int)$parent->getId(),
                'sku' => (string)$parent->getSku(),
                'name' => $name,
                'type' => $type,
                'link_type' => $this->resolveLinkType($type),
                'edit_url' => $this->backendUrl->getUrl('catalog/product/edit', ['id' => $parent->getId()])
            ];
        }
        return $rows;
    }

    /**
     * @param string $productType
     * @return string
     */
    private function resolveLinkType(string $productType): string
    {
        return match ($productType) {
            Configurable::TYPE_CODE => 'configurable',
            Bundle::TYPE_CODE => 'bundle',
            Grouped::TYPE_CODE => 'grouped',
            default => 'relation'
        };
    }
}
