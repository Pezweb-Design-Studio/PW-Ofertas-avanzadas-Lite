<?php
namespace PW\OfertasAvanzadas\Strategies;

interface DiscountStrategy {
    public function canApply(array $cart, array $config, array $conditions): bool;
    public function calculate(array $cart, array $config): array;
    public static function getMeta(): array;
    public static function getConfigFields(): array;
}