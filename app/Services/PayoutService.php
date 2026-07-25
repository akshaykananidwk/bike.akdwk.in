<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Settings;

/**
 * Withdrawal / payout lifecycle for shops and agencies.
 * A request reserves nothing until approved; on "mark paid" the wallet is
 * debited (never below zero). Bank details are required to request.
 */
class PayoutService
{
    /**
     * Create a withdrawal request. Throws on validation failure.
     * @return int payout id
     */
    public static function request(string $ownerType, int $ownerId, float $amount): int
    {
        $min = (float)Settings::get('min_withdrawal', 500);
        if ($amount < $min) {
            throw new \RuntimeException('Minimum withdrawal is ' . money($min) . '.');
        }
        $balance = WalletService::balance($ownerType, $ownerId);
        if ($amount > $balance + 0.001) {
            throw new \RuntimeException('Amount exceeds your wallet balance.');
        }
        $bank = self::bankSnapshot($ownerType, $ownerId);
        if (empty($bank['bank_account']) && empty($bank['upi_id'])) {
            throw new \RuntimeException('Please add your bank/UPI details before requesting a withdrawal.');
        }
        // Block duplicate pending requests.
        $pending = Database::scalar(
            "SELECT COUNT(*) FROM {p}payout_requests WHERE owner_type=? AND owner_id=? AND status IN ('pending','approved')",
            [$ownerType, $ownerId]
        );
        if ($pending > 0) {
            throw new \RuntimeException('You already have a withdrawal in progress.');
        }

        return Database::insert('payout_requests', [
            'owner_type'    => $ownerType,
            'owner_id'      => $ownerId,
            'amount'        => round($amount, 2),
            'status'        => 'pending',
            'bank_snapshot' => json_encode($bank, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public static function approve(int $payoutId, int $adminId): void
    {
        Database::run(
            "UPDATE {p}payout_requests SET status='approved', processed_by=?, processed_at=? WHERE id=? AND status='pending'",
            [$adminId, now(), $payoutId]
        );
    }

    public static function reject(int $payoutId, int $adminId, string $note = ''): void
    {
        Database::run("UPDATE {p}payout_requests SET status='rejected', processed_by=?, processed_at=?, note=? WHERE id=? AND status IN ('pending','approved')", [$adminId, now(), $note ?: null, $payoutId]);
    }

    /** Mark a payout as paid: debit the wallet atomically and store UTR/proof. */
    public static function markPaid(int $payoutId, int $adminId, string $utr, ?string $proof = null): void
    {
        Database::transaction(function () use ($payoutId, $adminId, $utr, $proof) {
            $po = Database::fetch("SELECT * FROM {p}payout_requests WHERE id=? FOR UPDATE", [$payoutId]);
            if (!$po || $po['status'] === 'paid') { return; }
            WalletService::debit($po['owner_type'], (int)$po['owner_id'], (float)$po['amount'], 'payout', $payoutId, 'Withdrawal', true);
            Database::update('payout_requests', [
                'status'       => 'paid',
                'utr'          => $utr ?: null,
                'proof_image'  => $proof,
                'processed_by' => $adminId,
                'processed_at' => now(),
            ], ['id' => $payoutId]);
        });
    }

    public static function bankSnapshot(string $ownerType, int $ownerId): array
    {
        $table = $ownerType === 'shop' ? 'shops' : 'agencies';
        $row = Database::fetch("SELECT bank_holder, bank_account, bank_ifsc, bank_name, upi_id FROM {p}{$table} WHERE id=?", [$ownerId]);
        return $row ?: [];
    }
}
