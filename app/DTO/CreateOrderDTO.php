<?php

namespace App\DTO;

readonly class CreateOrderDTO
{
    /**
     * @param int $customerId
     * @param array<int, array{product_id: int, quantity: int}> $items
     */
    public function __construct(
        public int $customerId,
        public array $items,
    ) {}
}
