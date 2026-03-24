import { NextRequest, NextResponse } from "next/server";
import Stripe from "stripe";
import { prisma } from "@buffetpro/db";
import { SubscriptionStatus, SubscriptionPlan } from "@buffetpro/types";

const stripe = new Stripe(process.env.STRIPE_SECRET_KEY!, {
  apiVersion: "2023-10-16",
});

/**
 * Stripe webhook endpoint.
 *
 * Handles:
 * - checkout.session.completed  → create/activate subscription
 * - customer.subscription.updated → sync plan & status changes
 * - customer.subscription.deleted → mark subscription as canceled
 * - invoice.payment_failed       → mark subscription as past_due
 */
export async function POST(req: NextRequest) {
  const body = await req.text();
  const sig = req.headers.get("stripe-signature");

  if (!sig) {
    return NextResponse.json({ error: "Missing stripe-signature header" }, { status: 400 });
  }

  let event: Stripe.Event;
  try {
    event = stripe.webhooks.constructEvent(
      body,
      sig,
      process.env.STRIPE_WEBHOOK_SECRET!
    );
  } catch (err: unknown) {
    const message = err instanceof Error ? err.message : "Unknown error";
    return NextResponse.json({ error: `Webhook Error: ${message}` }, { status: 400 });
  }

  try {
    switch (event.type) {
      case "checkout.session.completed": {
        const session = event.data.object as Stripe.Checkout.Session;
        if (session.mode === "subscription") {
          await handleCheckoutSessionCompleted(session);
        }
        break;
      }

      case "customer.subscription.updated": {
        const sub = event.data.object as Stripe.Subscription;
        await handleSubscriptionUpdated(sub);
        break;
      }

      case "customer.subscription.deleted": {
        const sub = event.data.object as Stripe.Subscription;
        await prisma.subscription.updateMany({
          where: { stripeSubscriptionId: sub.id },
          data: { status: SubscriptionStatus.CANCELED },
        });
        break;
      }

      case "invoice.payment_failed": {
        const invoice = event.data.object as Stripe.Invoice;
        if (invoice.subscription) {
          await prisma.subscription.updateMany({
            where: { stripeSubscriptionId: invoice.subscription as string },
            data: { status: SubscriptionStatus.PAST_DUE },
          });
        }
        break;
      }
    }
  } catch (err) {
    console.error("Error processing Stripe webhook:", err);
    return NextResponse.json({ error: "Internal server error" }, { status: 500 });
  }

  return NextResponse.json({ received: true });
}

async function handleCheckoutSessionCompleted(
  session: Stripe.Checkout.Session
) {
  const hotelId = session.metadata?.hotelId;
  if (!hotelId || !session.subscription) return;

  const stripeSub = await stripe.subscriptions.retrieve(
    session.subscription as string
  );

  const priceId = stripeSub.items.data[0]?.price?.id ?? "";
  const plan = resolvePlan(priceId);

  await prisma.subscription.upsert({
    where: { hotelId },
    update: {
      stripeSubscriptionId: stripeSub.id,
      stripeCustomerId: session.customer as string,
      stripePriceId: priceId,
      plan,
      status: mapStripeStatus(stripeSub.status),
      currentPeriodStart: new Date(stripeSub.current_period_start * 1000),
      currentPeriodEnd: new Date(stripeSub.current_period_end * 1000),
      cancelAtPeriodEnd: stripeSub.cancel_at_period_end,
    },
    create: {
      hotelId,
      stripeCustomerId: session.customer as string,
      stripeSubscriptionId: stripeSub.id,
      stripePriceId: priceId,
      plan,
      status: mapStripeStatus(stripeSub.status),
      currentPeriodStart: new Date(stripeSub.current_period_start * 1000),
      currentPeriodEnd: new Date(stripeSub.current_period_end * 1000),
      cancelAtPeriodEnd: stripeSub.cancel_at_period_end,
    },
  });
}

async function handleSubscriptionUpdated(sub: Stripe.Subscription) {
  const priceId = sub.items.data[0]?.price?.id ?? "";
  await prisma.subscription.updateMany({
    where: { stripeSubscriptionId: sub.id },
    data: {
      stripePriceId: priceId,
      plan: resolvePlan(priceId),
      status: mapStripeStatus(sub.status),
      currentPeriodStart: new Date(sub.current_period_start * 1000),
      currentPeriodEnd: new Date(sub.current_period_end * 1000),
      cancelAtPeriodEnd: sub.cancel_at_period_end,
    },
  });
}

function mapStripeStatus(status: Stripe.Subscription.Status): SubscriptionStatus {
  const map: Record<string, SubscriptionStatus> = {
    active: SubscriptionStatus.ACTIVE,
    trialing: SubscriptionStatus.TRIALING,
    past_due: SubscriptionStatus.PAST_DUE,
    canceled: SubscriptionStatus.CANCELED,
    unpaid: SubscriptionStatus.UNPAID,
  };
  return map[status] ?? SubscriptionStatus.ACTIVE;
}

function resolvePlan(priceId: string): SubscriptionPlan {
  if (priceId === process.env.STRIPE_PRICE_PROFESSIONAL) return SubscriptionPlan.PROFESSIONAL;
  if (priceId === process.env.STRIPE_PRICE_ENTERPRISE) return SubscriptionPlan.ENTERPRISE;
  return SubscriptionPlan.STARTER;
}
