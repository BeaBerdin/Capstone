<?php

namespace App\Support;

use App\Models\Transaction;

/**
 * The PayMongo payment methods PATHWISE offers, read from
 * PAYMONGO_PAYMENT_METHODS (comma-separated). Used for the checkout
 * request, the labels students see and the server validation.
 */
class PaymentMethods
{
    /**
     * Methods PATHWISE supports, with the label students see.
     * Keys are PayMongo's payment_method_types values.
     */
    public const SUPPORTED = [
        'gcash' => 'GCash',
        'paymaya' => 'Maya',
        'card' => 'Card',
    ];

    public const DEFAULT = 'gcash,card,paymaya';

    /**
     * Manual payment-proof option that is not a PayMongo method.
     */
    public const BANK_TRANSFER = 'Bank Transfer';

    /**
     * Enabled PayMongo method keys, in the configured order. Unknown values
     * are ignored; if nothing valid is configured, the default list is used.
     *
     * @return array<int, string>
     */
    public static function enabled(): array
    {
        $enabled = self::parse((string) config('services.paymongo.payment_methods', self::DEFAULT));

        return $enabled !== [] ? $enabled : self::parse(self::DEFAULT);
    }

    /**
     * Labels of the enabled methods, e.g. ['gcash' => 'GCash', 'paymaya' => 'Maya'].
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            self::enabled(),
            array_map(fn (string $method) => self::SUPPORTED[$method], self::enabled())
        );
    }

    /**
     * Options for the manual payment-proof form: the enabled methods'
     * labels plus bank transfer.
     *
     * @return array<int, string>
     */
    public static function proofOptions(): array
    {
        return [...array_values(self::labels()), self::BANK_TRANSFER];
    }

    /**
     * Display label for a stored transaction payment method. PayMongo keys
     * (gcash, paymaya, card) get their label; anything else that older
     * transactions stored (PayMongo, GCash, Maya, Bank Transfer) is shown
     * as it is.
     */
    public static function label(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        return self::SUPPORTED[strtolower(trim($stored))] ?? $stored;
    }

    /**
     * A PayMongo method key reported by PayMongo (gcash, paymaya, card),
     * or null when the value is missing or not supported.
     */
    public static function normalize(mixed $method): ?string
    {
        if (! is_string($method)) {
            return null;
        }

        $method = strtolower(trim($method));

        return array_key_exists($method, self::SUPPORTED) ? $method : null;
    }

    /**
     * What to show for a transaction's payment method: "Maya via PayMongo"
     * when PayMongo reported the method, otherwise the stored method as
     * before (e.g. "PayMongo" for older online payments, "GCash" for a
     * manual receipt).
     */
    public static function forTransaction(?Transaction $transaction): ?string
    {
        if ($transaction === null) {
            return null;
        }

        $paymongoMethod = self::normalize($transaction->paymongo_method);

        if ($paymongoMethod !== null) {
            return self::SUPPORTED[$paymongoMethod].' via PayMongo';
        }

        return self::label($transaction->payment_method);
    }

    /**
     * @return array<int, string>
     */
    private static function parse(string $list): array
    {
        $methods = array_map(
            fn (string $method) => strtolower(trim($method)),
            explode(',', $list)
        );

        return array_values(array_unique(array_filter(
            $methods,
            fn (string $method) => array_key_exists($method, self::SUPPORTED)
        )));
    }
}
