import type { Metadata } from "next";

export const metadata: Metadata = { title: "Pricing" };

const plans = [
  {
    name: "Starter",
    price: "€49",
    period: "/month",
    description: "Perfect for small hotels and restaurants.",
    features: [
      "Up to 5 users",
      "Dish library (100 items)",
      "Buffer composition",
      "PDF exports",
      "Email support",
    ],
    cta: "Start free trial",
    highlight: false,
  },
  {
    name: "Professional",
    price: "€149",
    period: "/month",
    description: "For growing properties with full coordination needs.",
    features: [
      "Unlimited users",
      "Unlimited dishes",
      "Cost engine",
      "Coordinator module",
      "Multi-language menus",
      "Priority support",
    ],
    cta: "Start free trial",
    highlight: true,
  },
  {
    name: "Enterprise",
    price: "Custom",
    period: "",
    description: "White-label, API access, and dedicated infrastructure.",
    features: [
      "Everything in Professional",
      "White-label branding",
      "REST / tRPC API access",
      "AI auto-compose (Phase 3)",
      "SLA & dedicated support",
    ],
    cta: "Contact sales",
    highlight: false,
  },
];

export default function PricingPage() {
  return (
    <main className="min-h-screen bg-gray-50 py-24">
      <div className="mx-auto max-w-7xl px-6">
        <div className="text-center">
          <h1 className="text-4xl font-extrabold text-gray-900">Simple, transparent pricing</h1>
          <p className="mt-4 text-lg text-gray-500">Start free for 14 days. No credit card required.</p>
        </div>

        <div className="mt-16 grid gap-8 md:grid-cols-3">
          {plans.map((plan) => (
            <div
              key={plan.name}
              className={`rounded-xl border p-8 shadow-sm ${
                plan.highlight
                  ? "border-brand-600 bg-brand-600 text-white"
                  : "border-gray-200 bg-white text-gray-900"
              }`}
            >
              <h2 className="text-xl font-bold">{plan.name}</h2>
              <div className="mt-4 flex items-end gap-1">
                <span className="text-4xl font-extrabold">{plan.price}</span>
                <span className={`text-sm ${plan.highlight ? "text-blue-100" : "text-gray-500"}`}>
                  {plan.period}
                </span>
              </div>
              <p className={`mt-3 text-sm ${plan.highlight ? "text-blue-100" : "text-gray-500"}`}>
                {plan.description}
              </p>
              <ul className="mt-6 space-y-2">
                {plan.features.map((f) => (
                  <li key={f} className="flex items-center gap-2 text-sm">
                    <span>✓</span>
                    {f}
                  </li>
                ))}
              </ul>
              <button
                className={`mt-8 w-full rounded-md py-2 text-sm font-medium transition-colors ${
                  plan.highlight
                    ? "bg-white text-brand-600 hover:bg-blue-50"
                    : "bg-brand-600 text-white hover:bg-brand-700"
                }`}
              >
                {plan.cta}
              </button>
            </div>
          ))}
        </div>
      </div>
    </main>
  );
}
