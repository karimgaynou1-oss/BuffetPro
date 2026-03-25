<?php

namespace App\Http\Controllers\Api;

use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends BaseApiController
{
    /**
     * POST /api/stripe/webhook
     */
    public function handle(Request $request): JsonResponse
    {
        $webhookSecret = config('services.stripe.webhook_secret', env('STRIPE_WEBHOOK_SECRET'));
        $payload       = $request->getContent();
        $sigHeader     = $request->header('Stripe-Signature', '');

        if ($webhookSecret && !$this->verifyStripeSignature($payload, $sigHeader, $webhookSecret)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        if (!$event) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        match ($event['type'] ?? '') {
            'checkout.session.completed'  => $this->handleCheckoutCompleted($event),
            'invoice.paid'                => $this->handleInvoicePaid($event),
            'invoice.payment_failed'      => $this->handleInvoicePaymentFailed($event),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
            default                       => null,
        };

        return response()->json(['received' => true]);
    }

    private function handleCheckoutCompleted(array $event): void
    {
        $session = $event['data']['object'] ?? [];
        $customerId = $session['customer'] ?? null;
        $subscriptionId = $session['subscription'] ?? null;

        if (!$customerId) {
            return;
        }

        $hotel = Hotel::where('stripe_customer_id', $customerId)->first();
        if ($hotel) {
            $hotel->update([
                'stripe_subscription_id' => $subscriptionId,
                'subscription_status'    => 'active',
            ]);
        }
    }

    private function handleInvoicePaid(array $event): void
    {
        $invoice    = $event['data']['object'] ?? [];
        $customerId = $invoice['customer'] ?? null;
        if (!$customerId) {
            return;
        }

        $hotel = Hotel::where('stripe_customer_id', $customerId)->first();
        if ($hotel) {
            $periodEnd = isset($invoice['lines']['data'][0]['period']['end'])
                ? \Carbon\Carbon::createFromTimestamp($invoice['lines']['data'][0]['period']['end'])
                : now()->addMonth();

            $hotel->update([
                'subscription_status'   => 'active',
                'subscription_ends_at'  => $periodEnd,
            ]);
        }
    }

    private function handleInvoicePaymentFailed(array $event): void
    {
        $invoice    = $event['data']['object'] ?? [];
        $customerId = $invoice['customer'] ?? null;
        if (!$customerId) {
            return;
        }

        $hotel = Hotel::where('stripe_customer_id', $customerId)->first();
        if ($hotel) {
            $hotel->update(['subscription_status' => 'past_due']);
        }
    }

    private function handleSubscriptionDeleted(array $event): void
    {
        $sub        = $event['data']['object'] ?? [];
        $customerId = $sub['customer'] ?? null;
        if (!$customerId) {
            return;
        }

        $hotel = Hotel::where('stripe_customer_id', $customerId)->first();
        if ($hotel) {
            $hotel->update(['subscription_status' => 'cancelled']);
        }
    }

    private function handleSubscriptionUpdated(array $event): void
    {
        $sub        = $event['data']['object'] ?? [];
        $customerId = $sub['customer'] ?? null;
        if (!$customerId) {
            return;
        }

        $hotel = Hotel::where('stripe_customer_id', $customerId)->first();
        if (!$hotel) {
            return;
        }

        $status = match ($sub['status'] ?? '') {
            'active'             => 'active',
            'trialing'           => 'trial',
            'past_due'           => 'past_due',
            'canceled', 'unpaid' => 'cancelled',
            default              => $hotel->subscription_status,
        };

        // Determine plan from price
        $priceId = $sub['items']['data'][0]['price']['id'] ?? '';
        $plan    = $hotel->plan; // Keep existing unless we have a mapping

        $hotel->update([
            'subscription_status' => $status,
            'plan'                => $plan,
        ]);
    }

    private function verifyStripeSignature(string $payload, string $sigHeader, string $secret): bool
    {
        // Parse timestamp and signatures from header
        $parts = explode(',', $sigHeader);
        $ts    = null;
        $sigs  = [];
        foreach ($parts as $part) {
            [$key, $value] = explode('=', $part, 2);
            if ($key === 't') {
                $ts = (int) $value;
            } elseif ($key === 'v1') {
                $sigs[] = $value;
            }
        }

        if (!$ts || empty($sigs)) {
            return false;
        }

        // Timestamp tolerance: 5 minutes
        if (abs(time() - $ts) > 300) {
            return false;
        }

        $signedPayload = $ts . '.' . $payload;
        $expected      = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($sigs as $sig) {
            if (hash_equals($expected, $sig)) {
                return true;
            }
        }

        return false;
    }
}
