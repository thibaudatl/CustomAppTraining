<?php

declare(strict_types=1);

namespace App\PimApi\Model;

class Product
{
    /**
     * @param array<ProductValue> $attributes
     */
    public function __construct(
        public readonly string $uuid,
        public readonly string $label,
        public string $description,
        public readonly array $attributes = [],
    ) {
    }
}
