<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MGH\ParentProducts\Test\Unit\Model;

use MGH\ParentProducts\Model\ParentProductsProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Backend\Model\UrlInterface;
use PHPUnit\Framework\TestCase;

class ParentProductsProviderTest extends TestCase
{
    private function stubProduct(int $id, string $sku, string $name, string $type): object
    {
        return new class($id,$sku,$name,$type) {
            public function __construct(private int $id, private string $sku, private string $name, private string $type) {}
            public function getId(): int { return $this->id; }
            public function getSku(): string { return $this->sku; }
            public function getName(): string { return $this->name; }
            public function getTypeId(): string { return $this->type; }
        };
    }

    private function buildProvider(
        array $fetchColReturns,
        array $products,
        ?string $urlPattern = '/admin/catalog/product/edit'
    ): ParentProductsProvider {
        $resource = $this->createMock(ResourceConnection::class);
        $connection = $this->createMock(AdapterInterface::class);

        // Mock select() chaining to return a dummy object supporting where() & from()
        $connection->method('select')->willReturn(new class {
            public function from(...$a){return $this;} public function where(...$a){return $this;}
        });

        // Sequential fetchCol return simulation
        $connection->method('fetchCol')->willReturnOnConsecutiveCalls(...$fetchColReturns);

        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnCallback(fn($n) => $n);

        $collection = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $collection->method('addAttributeToSelect')->willReturnSelf();
        $collection->method('addFieldToFilter')->willReturnSelf();
        // Iterator over provided products
        $collection->method('getIterator')->willReturn(new \ArrayIterator($products));

        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->willReturn($urlPattern);

        return new ParentProductsProvider($resource, $factory, $url);
    }

    public function testReturnsEmptyForInvalidId(): void
    {
        $provider = $this->buildProvider([], [], '/edit');
        $this->assertSame([], $provider->getParentProducts(0));
        $this->assertSame([], $provider->getParentProducts(-5));
    }

    public function testReturnsEmptyWhenNoParentIdsFound(): void
    {
        // relation returns [], super_link returns []
        $provider = $this->buildProvider([[], []], [], '/edit');
        $this->assertSame([], $provider->getParentProducts(42));
    }

    public function testFallbackToSkuWhenNameEmpty(): void
    {
        $p1 = $this->stubProduct(101,'SKU101','', 'configurable');
        $p2 = $this->stubProduct(102,'SKU102','Parent 102','grouped');

        // relation returns parent IDs, super_link not used (empty)
        $provider = $this->buildProvider([[101, 102], []], [$p1, $p2]);
        $rows = $provider->getParentProducts(777);
        $this->assertCount(2, $rows);
        $this->assertSame('SKU101', $rows[0]['name'], 'Empty name should fallback to SKU');
        $this->assertSame('Parent 102', $rows[1]['name']);
    }

    public function testLinkTypeMapping(): void
    {
        $configurable = $this->stubProduct(11,'C11','C','configurable');
        $bundle = $this->stubProduct(12,'B12','B','bundle');
        $grouped = $this->stubProduct(13,'G13','G','grouped');
        $simple = $this->stubProduct(14,'S14','S','simple');

        $provider = $this->buildProvider([[11,12,13,14], []], [$configurable,$bundle,$grouped,$simple]);
        $rows = $provider->getParentProducts(999);
        $this->assertSame(['configurable','bundle','grouped','relation'], array_column($rows, 'link_type'));
    }

    public function testEditUrlPresent(): void
    {
        $p = $this->stubProduct(501,'SKU501','Parent 501','grouped');
        $provider = $this->buildProvider([[501], []], [$p], '/admin/catalog/product/edit/id/501');
        $rows = $provider->getParentProducts(300);
        $this->assertCount(1, $rows);
        $this->assertArrayHasKey('edit_url', $rows[0]);
        $this->assertStringContainsString('/admin/catalog/product/edit', $rows[0]['edit_url']);
    }
}

