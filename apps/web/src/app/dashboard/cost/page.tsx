import { getSessionUser } from "@buffetpro/auth";
import { redirect } from "next/navigation";
import type { Metadata } from "next";

export const metadata: Metadata = { title: "Cost Engine" };

export default async function CostPage() {
  const user = await getSessionUser();
  if (!user) redirect("/login");

  return (
    <main className="min-h-screen bg-gray-50 p-8">
      <div className="mx-auto max-w-7xl">
        <h1 className="text-2xl font-bold text-gray-900">Cost Engine</h1>
        <p className="mt-1 text-sm text-gray-500">
          Track cost per portion, budget vs actual, and cost by category
        </p>

        <div className="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
          {[
            { label: "Cost / 10 PKU", value: "—", icon: "📊" },
            { label: "Portions calc", value: "—", icon: "🔢" },
            { label: "Budget vs Actual", value: "—", icon: "📈" },
            { label: "Cost by category", value: "—", icon: "🏷️" },
          ].map((stat) => (
            <div
              key={stat.label}
              className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
              <span className="text-2xl">{stat.icon}</span>
              <p className="mt-3 text-2xl font-bold text-gray-900">{stat.value}</p>
              <p className="mt-1 text-sm text-gray-500">{stat.label}</p>
            </div>
          ))}
        </div>
      </div>
    </main>
  );
}
