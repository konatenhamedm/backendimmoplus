<?php

namespace App\Service;

/**
 * DTO pour les alertes de stock (utilisé dans les services de notification)
 */
class StockDeficit
{
    private string $productName;
    private int $currentStock;
    private int $requiredQuantity;
    private int $deficit;

    public function __construct(string $productName, int $currentStock, int $requiredQuantity)
    {
        $this->productName = $productName;
        $this->currentStock = $currentStock;
        $this->requiredQuantity = $requiredQuantity;
        $this->deficit = $requiredQuantity - $currentStock;
    }

    public function toArray(): array
    {
        return [
            'product_name' => $this->productName,
            'current_stock' => $this->currentStock,
            'required_quantity' => $this->requiredQuantity,
            'deficit' => $this->deficit
        ];
    }

    public function getDeficit(): int
    {
        return $this->deficit;
    }
}
