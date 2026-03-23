<?php

namespace App\Support;

use Illuminate\Support\Collection;

class SessionCart
{
    public const SESSION_KEY = 'ecommerce.cart';

    public function raw(): array
    {
        $cart = session(self::SESSION_KEY, []);

        return is_array($cart) ? $cart : [];
    }

    public function items(): Collection
    {
        return collect($this->raw());
    }

    public function get(string $productNo): ?array
    {
        $cart = $this->raw();

        return $cart[$productNo] ?? null;
    }

    public function add(array $product, int $qty): void
    {
        $qty = max(1, $qty);
        $productNo = (string) ($product['product_no'] ?? '');
        if ($productNo === '') {
            return;
        }

        $cart = $this->raw();
        $currentQty = isset($cart[$productNo]) ? (int) $cart[$productNo]['qty'] : 0;

        $cart[$productNo] = [
            'product_no' => $productNo,
            'product_name' => (string) ($product['product_name'] ?? ''),
            'product_type_name' => (string) ($product['product_type_name'] ?? ''),
            'unit_measure' => (string) ($product['unit_measure'] ?? ''),
            'sell_price' => (float) ($product['sell_price'] ?? 0),
            'qty' => $currentQty + $qty,
        ];

        session([self::SESSION_KEY => $cart]);
    }

    public function setQuantity(string $productNo, int $qty): void
    {
        $cart = $this->raw();
        if (! isset($cart[$productNo])) {
            return;
        }

        if ($qty <= 0) {
            unset($cart[$productNo]);
        } else {
            $cart[$productNo]['qty'] = $qty;
        }

        session([self::SESSION_KEY => $cart]);
    }

    public function remove(string $productNo): void
    {
        $cart = $this->raw();
        unset($cart[$productNo]);
        session([self::SESSION_KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function totalQuantity(): int
    {
        return $this->items()
            ->sum(fn (array $item): int => (int) ($item['qty'] ?? 0));
    }
}
