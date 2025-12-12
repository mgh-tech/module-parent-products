<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MGH\ParentProducts\Ui\DataProvider\Product\Form\Modifier;

use Exception;
use MGH\ParentProducts\Api\ParentProductsProviderInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class ParentProducts implements ModifierInterface
{
    private const XML_PATH_ENABLED = 'parentproducts/general/enable';
    private const ACL_RESOURCE = 'MGH_ParentProducts::parent_products';
    private const FIELDSET_NAME = 'parent_products_fieldset';
    private const FIELDSET_SORT_ORDER = 210;

    public function __construct(
        private readonly LocatorInterface $locator,
        private readonly ParentProductsProviderInterface $provider,
        private readonly AuthorizationInterface $authorization,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ImageHelper $imageHelper,
        private readonly ProductCollectionFactory $productCollectionFactory
    ) {
    }

    public function modifyMeta(array $meta): array
    {
        if (!$this->canModify()) {
            return $meta;
        }

        $productId = $this->getProductId();
        if ($productId <= 0) {
            return $meta;
        }

        $rows = $this->provider->getParentProducts($productId);
        if (empty($rows)) {
            return $meta;
        }

        $meta[self::FIELDSET_NAME] = $this->buildFieldsetMeta(count($rows));

        return $meta;
    }

    public function modifyData(array $data): array
    {
        if (!$this->canModify()) {
            return $data;
        }

        $productId = $this->getProductId();
        if ($productId <= 0) {
            return $data;
        }

        $rows = $this->provider->getParentProducts($productId);
        if (empty($rows)) {
            return $data;
        }

        $thumbnailMap = $this->loadThumbnails($rows);

        foreach ($rows as &$row) {
            $row['thumbnail_url'] = $thumbnailMap[(int)($row['id'] ?? 0)] ?? '';
        }
        unset($row);

        $data[$productId]['product']['parent_products'] = $rows;

        return $data;
    }

    private function canModify(): bool
    {
        return $this->isEnabled() && $this->authorization->isAllowed(self::ACL_RESOURCE);
    }

    private function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    private function getProductId(): int
    {
        return (int) ($this->locator->getProduct()?->getId() ?? 0);
    }

    private function loadThumbnails(array $rows): array
    {
        $ids = array_unique(array_filter(array_column($rows, 'id')));
        if (empty($ids)) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['small_image', 'thumbnail', 'image']);
        $collection->addFieldToFilter('entity_id', ['in' => $ids]);

        $thumbnailMap = [];
        foreach ($collection as $product) {
            $productId = (int)$product->getId();
            try {
                $thumbnailMap[$productId] = $this->imageHelper
                    ->init($product, 'product_listing_thumbnail')
                    ->getUrl();
            } catch (Exception) {
                // If exception occurs, get the placeholder image
                try {
                    $thumbnailMap[$productId] = $this->imageHelper
                        ->getDefaultPlaceholderUrl('thumbnail');
                } catch (Exception) {
                    $thumbnailMap[$productId] = '';
                }
            }
        }

        return $thumbnailMap;
    }

    private function buildFieldsetMeta(int $count): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'fieldset',
                        'label' => __('Parent Products (%1)', $count),
                        'collapsible' => true,
                        'opened' => true,
                        'sortOrder' => self::FIELDSET_SORT_ORDER,
                    ],
                ],
            ],
            'children' => [
                'parent_products' => $this->buildDynamicRowsMeta()
            ]
        ];
    }

    private function buildDynamicRowsMeta(): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'dynamicRows',
                        'component' => 'Magento_Ui/js/dynamic-rows/dynamic-rows',
                        'dataScope' => 'data.product',
                        'label' => false,
                        'columnsHeader' => true,
                        'renderDefaultRecord' => false,
                        'addButton' => false,
                        'dndConfig' => ['enabled' => false],
                        'template' => 'ui/dynamic-rows/templates/grid',
                        'additionalClasses' => 'admin__field-wide admin__control-table',
                        'visible' => true,
                        'sortOrder' => 10,
                        'indexField' => 'id'
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'container',
                                'isTemplate' => true,
                                'is_collection' => true,
                                'dataScope' => '',
                                'disabled' => false,
                            ],
                        ],
                    ],
                    'children' => [
                        'id' => $this->buildColumnMeta('ID', 'id', 'ui/dynamic-rows/cells/text', 10),
                        'thumbnail' => $this->buildColumnMeta('Thumbnail', 'thumbnail_url', 'ui/dynamic-rows/cells/thumbnail', 15),
                        'sku' => $this->buildColumnMeta('SKU', 'sku', 'ui/dynamic-rows/cells/text', 20),
                        'name' => $this->buildColumnMeta('Name', 'name', 'ui/dynamic-rows/cells/text', 30),
                        'type' => $this->buildColumnMeta('Type', 'type', 'ui/dynamic-rows/cells/text', 40),
                        'link_type' => $this->buildColumnMeta('Link Type', 'link_type', 'ui/dynamic-rows/cells/text', 50),
                        'edit_url' => $this->buildColumnMeta('Edit URL', 'edit_url', 'MGH_ParentProducts/dynamic-rows/cells/edit-link', 60),
                    ],
                ],
            ],
        ];
    }

    private function buildColumnMeta(string $label, string $dataScope, string $template, int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'field',
                        'formElement' => 'input',
                        'dataType' => 'text',
                        'elementTmpl' => $template,
                        'label' => __($label),
                        'dataScope' => $dataScope,
                        'disabled' => false,
                        'visible' => true,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];
    }
}
