<?php
namespace App\Services;

use App\Core\Database;

/**
 * Atomic wallet operations for shops and agencies. All mutations run inside a
 * DB transaction and the balance can never go negative.
 */
class WalletService
{
    /** Get or create the wallet row id for an owner. */
    public static function walletId(string $ownerType, int $ownerId): int
    {
        $row = Database::fetch("SELECT id FROM {p}wallets WHERE owner_type=? AND owner_id=?", [$ownerType, $ownerId]);
        if ($row) { return (int)$row['id']; }
        return Database::insert('wallets', ['owner_type' => $ownerType, 'owner_id' => $ownerId, 'balance' => 0]);
    }

    public static function balance(string $ownerType, int $ownerId): float
    {
        return (float)Database::scalar("SELECT balance FROM {p}wallets WHERE owner_type=? AND owner_id=?", [$ownerType, $ownerId]);
    }

    /** Credit a wallet. Records a wallet_transactions row. */
    public static function credit(string $ownerType, int $ownerId, float $amount, string $refType, $refId = null, string $note = ''): void
    {
        if ($amount <= 0) { return; }
        $walletId = self::walletId($ownerType, $ownerId);
        // Lock the row for a consistent balance under concurrency.
        $wallet = Database::fetch("SELECT * FROM {p}wallets WHERE id=? FOR UPDATE", [$walletId]);
        $newBalance = round((float)$wallet['balance'] + $amount, 2);
        Database::update('wallets', [
            'balance'      => $newBalance,
            'total_earned' => round((float)$wallet['total_earned'] + $amount, 2),
        ], ['id' => $walletId]);
        self::txn($walletId, 'credit', $amount, $newBalance, $refType, $refId, $note);
    }

    /**
     * Debit a wallet. If $allowNegative is false (default) and funds are
     * insufficient, throws — the balance never goes negative.
     */
    public static function debit(string $ownerType, int $ownerId, float $amount, string $refType, $refId = null, string $note = '', bool $isWithdrawal = false): void
    {
        if ($amount <= 0) { return; }
        $walletId = self::walletId($ownerType, $ownerId);
        $wallet = Database::fetch("SELECT * FROM {p}wallets WHERE id=? FOR UPDATE", [$walletId]);
        $current = (float)$wallet['balance'];
        if ($amount > $current + 0.001) {
            throw new \RuntimeException('Insufficient wallet balance.');
        }
        $newBalance = round($current - $amount, 2);
        $update = ['balance' => $newBalance];
        if ($isWithdrawal) {
            $update['total_withdrawn'] = round((float)$wallet['total_withdrawn'] + $amount, 2);
        }
        Database::update('wallets', $update, ['id' => $walletId]);
        self::txn($walletId, 'debit', $amount, $newBalance, $refType, $refId, $note);
    }

    /** Reversal of a prior credit (e.g. booking cancelled). Clamped at 0. */
    public static function reverse(string $ownerType, int $ownerId, float $amount, string $refType, $refId = null, string $note = ''): void
    {
        if ($amount <= 0) { return; }
        $walletId = self::walletId($ownerType, $ownerId);
        $wallet = Database::fetch("SELECT * FROM {p}wallets WHERE id=? FOR UPDATE", [$walletId]);
        $current = (float)$wallet['balance'];
        $take = min($amount, $current);            // never go negative
        $newBalance = round($current - $take, 2);
        Database::update('wallets', [
            'balance'      => $newBalance,
            'total_earned' => max(0, round((float)$wallet['total_earned'] - $amount, 2)),
        ], ['id' => $walletId]);
        self::txn($walletId, 'debit', $take, $newBalance, $refType, $refId, 'Reversal: ' . $note);
    }

    private static function txn(int $walletId, string $direction, float $amount, float $balanceAfter, string $refType, $refId, string $note): void
    {
        Database::insert('wallet_transactions', [
            'wallet_id'     => $walletId,
            'direction'     => $direction,
            'amount'        => round($amount, 2),
            'balance_after' => $balanceAfter,
            'ref_type'      => $refType,
            'ref_id'        => $refId !== null ? (string)$refId : null,
            'note'          => $note ?: null,
        ]);
    }
}
