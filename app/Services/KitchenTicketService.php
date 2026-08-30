<?php

namespace App\Services;

use App\Models\Order;

/**
 * Builds the data needed to render a Kitchen Order Ticket (KOT).
 *
 * The KOT is a kitchen ticket (not a payment bill). It is generated once per
 * order group (an order_code) for every successfully submitted order, and can
 * be manually reprinted. Reprinting never creates another order.
 */
class KitchenTicketService
{
    /**
     * List the order line items for a given order group, joined with the
     * product/branch/waiter/session information needed by the KOT.
     */
    public function ticketData(string $orderCode): ?array
    {
        $orders = Order::with(['product', 'branch', 'waiter', 'customerSession'])
            ->where('order_code', $orderCode)
            ->orderBy('created_at')
            ->get();

        if ($orders->isEmpty()) {
            return null;
        }

        $first = $orders->first();

        return [
            'orderCode' => $orderCode,
            'sessionCode' => $first->customerSession?->session_code,
            'waiterName' => $first->waiter?->name,
            'branchName' => $first->branch?->name,
            'createdAt' => $first->created_at,
            'orderType' => $first->order_type,
            'notes' => $this->collectNotes($orders),
            'items' => $orders->map(function ($order) {
                return [
                    'name' => $order->product?->name ?? 'Product',
                    'quantity' => $order->quantity,
                    'size' => $order->size,
                    'notes' => $order->notes,
                ];
            })->values()->all(),
        ];
    }

    /**
     * Pick up item-level special instructions. Multiple line items can carry
     * their own notes, so we collect the non-empty ones in order.
     */
    private function collectNotes($orders): array
    {
        $notes = [];
        foreach ($orders as $order) {
            if (! empty($order->notes)) {
                $notes[] = $order->notes;
            }
        }
        return $notes;
    }
}
