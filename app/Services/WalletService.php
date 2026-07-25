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

    /**
     * Credit a wallet. If $holdHours > 0 the funds land in pending_balance and
     * only become withdrawable (moved to balance) after the hold period — this
     * is how the platform holds settlements for 48h before payout.
     */
    public static function credit(string $ownerType, int $ownerId, float $amount, string $refType, $refId = null, string $note = '', int $holdHours = 0): void
    {
        if ($amount <= 0) { return; }
        $walletId = self::walletId($ownerType, $ownerId);
        // Lock the row for a consistent balance under concurrency.
        $wallet = Database::fetch("SELECT * FROM {p}wallets WHERE id=? FOR UPDATE", [$walletId]);

        if ($holdHours > 0) {
            $newPending = round((float)($wallet['pending_balance'] ?? 0) + $amount, 2);
            Database::update('wallets', [
                'pending_balance' => $newPending,
                'total_earned'    => round((float)$wallet['total_earned'] + $amount, 2),
            ], ['id' => $walletId]);
            $holdUntil = date('Y-m-d H:i:s', time() + $holdHours * 3600);
            self::txn($walletId, 'credit', $amount, (float)$wallet['balance'], $refType, $refId, $note, $holdUntil, 0);
        } else {
            $newBalance = round((float)$wallet['balance'] + $amount, 2);
            Database::update('wallets', [
                'balance'      => $newBalance,
                'total_earned' => round((float)$wallet['total_earned'] + $amount, 2),
            ], ['id' => $walletId]);
            self::txn($walletId, 'credit', $amount, $newBalance, $refType, $refId, $note);
        }
    }

    /**
     * Release held funds whose hold period has elapsed: move from pending to
     * available balance. Called by cron. Returns the number released.
     */
    public static function releaseHeld(): int
    {
        $due = Database::fetchAll(
            "SELECT * FROM {p}wallet_transactions WHERE direction='credit' AND released=0 AND hold_until IS NOT NULL AND hold_until<=? ",
            [date('Y-m-d H:i:s')]
        );
        $count = 0;
        foreach ($due as $wt) {
            Database::transaction(function () use ($wt) {
                $w = Database::fetch("SELECT * FROM {p}wallets WHERE id=? FOR UPDATE", [$wt['wallet_id']]);
                if (!$w) { return; }
                $amount = (float)$wt['amount'];
                $newBalance = round((float)$w['balance'] + $amount, 2);
                $newPending = max(0, round((float)$w['pending_balance'] - $amount, 2));
                Database::update('wallets', ['balance' => $newBalance, 'pending_balance' => $newPending], ['id' => $w['id']]);
                Database::update('wallet_transactions', ['released' => 1, 'balance_after' => $newBalance], ['id' => $wt['id']]);
            });
            $count++;
        }
        return $count;
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

    private static function txn(int $walletId, string $direction, float $amount, float $balanceAfter, string $refType, $refId, string $note, ?string $holdUntil = null, int $released = 1): void
    {
        Database::insert('wallet_transactions', [
            'wallet_id'     => $walletId,
            'direction'     => $direction,
            'amount'        => round($amount, 2),
            'balance_after' => $balanceAfter,
            'ref_type'      => $refType,
            'ref_id'        => $refId !== null ? (string)$refId : null,
            'note'          => $note ?: null,
            'hold_until'    => $holdUntil,
            'released'      => $released,
        ]);
    }
}
