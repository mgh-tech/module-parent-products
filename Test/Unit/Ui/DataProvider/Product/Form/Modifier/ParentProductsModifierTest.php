<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MGH\ParentProducts\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use MGH\ParentProducts\Ui\DataProvider\Product\Form\Modifier\ParentProducts;
use MGH\ParentProducts\Api\ParentProductsProviderInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use PHPUnit\Framework\TestCase;

class ParentProductsModifierTest extends TestCase
{
    private function stubProduct(?int $id): object
    {
        return new class($id) {
            public function __construct(private ?int $id) {}
            public function getId(): ?int { return $this->id; }
        };
    }

    private function buildModifier(array $rows, bool $enabled = true, bool $allowed = true, ?int $productId = 555): ParentProducts
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('getProduct')->willReturn($this->stubProduct($productId));

        $provider = $this->createMock(ParentProductsProviderInterface::class);
        $provider->method('getParentProducts')->willReturn($rows);

        $auth = $this->createMock(AuthorizationInterface::class);
        $auth->method('isAllowed')->willReturn($allowed);

        $scope = $this->createMock(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturn($enabled ? '1' : '0');

        $imageHelper = $this->createMock(ImageHelper::class);

        $collection = $this->createMock(ProductCollection::class);
        $collection->method('setStoreId')->willReturnSelf();
        $collection->method('addAttributeToSelect')->willReturnSelf();
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([]));

        $collectionFactory = $this->createMock(ProductCollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        return new ParentProducts($locator, $provider, $auth, $scope, $imageHelper, $collectionFactory);
    }

    public function testMetaNotAddedWhenDisabled(): void
    {
        $modifier = $this->buildModifier(['dummy'], false, true);
        $meta = $modifier->modifyMeta([]);
        $this->assertArrayNotHasKey('parent_products_fieldset', $meta);
    }

    public function testMetaNotAddedWithoutParents(): void
    {
        $modifier = $this->buildModifier([], true, true);
        $meta = $modifier->modifyMeta([]);
        $this->assertArrayNotHasKey('parent_products_fieldset', $meta);
    }

    public function testMetaAddedWhenParentsExist(): void
    {
        $rows = [ ['id' => 7, 'sku' => 'S7', 'name' => 'Parent 7', 'type' => 'configurable', 'link_type' => 'configurable', 'edit_url' => '/edit'] ];
        $modifier = $this->buildModifier($rows, true, true);
        $meta = $modifier->modifyMeta([]);
        $this->assertArrayHasKey('parent_products_fieldset', $meta);
        $this->assertArrayHasKey('children', $meta['parent_products_fieldset']);
    }

    public function testDataInjectionSkippedWhenDisabled(): void
    {
        $modifier = $this->buildModifier(['x'], false, true);
        $this->assertSame([], $modifier->modifyData([]));
    }

    public function testDataInjectedWhenParentsExist(): void
    {
        $rows = [ ['id' => 1, 'sku' => 'A', 'name' => 'Parent A', 'type' => 'configurable', 'link_type' => 'configurable', 'edit_url' => '/edit'] ];
        $modifier = $this->buildModifier($rows, true, true);
        $data = $modifier->modifyData([]);
        $this->assertArrayHasKey(555, $data);
        $this->assertArrayHasKey('product', $data[555]);
        $this->assertArrayHasKey('parent_products', $data[555]['product']);
        $this->assertCount(1, $data[555]['product']['parent_products']);
    }

    public function testMetaShowsParentCountInLabel(): void
    {
        $rows = [ ['id'=>1,'sku'=>'A','name'=>'X','type'=>'configurable','link_type'=>'configurable','edit_url'=>'/e'], ['id'=>2,'sku'=>'B','name'=>'Y','type'=>'grouped','link_type'=>'grouped','edit_url'=>'/e2'] ];
        $modifier = $this->buildModifier($rows, true, true);
        $meta = $modifier->modifyMeta([]);
        $label = $meta['parent_products_fieldset']['arguments']['data']['config']['label'] ?? '';
        $labelString = (string)$label; // Convert Phrase to string
        $this->assertStringContainsString('(2)', $labelString);
        $this->assertNotEmpty($labelString);
    }

    public function testMetaNotAddedWhenProductIdMissing(): void
    {
        $rows = [['id' => 9, 'sku' => 'Z', 'name' => 'Z', 'type' => 'bundle', 'link_type' => 'bundle', 'edit_url' => '/z']];
        $modifier = $this->buildModifier($rows, true, true, null);
        $meta = $modifier->modifyMeta([]);
        $this->assertArrayNotHasKey('parent_products_fieldset', $meta);
    }
}
