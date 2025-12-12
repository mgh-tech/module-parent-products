<?php
/**
 * Copyright © MGH-tech. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MGH\ParentProducts\Api;

interface ParentProductsProviderInterface
{
    /**
     * @param int $productId
     * @return array
     */
    public function getParentProducts(int $productId): array;
}

