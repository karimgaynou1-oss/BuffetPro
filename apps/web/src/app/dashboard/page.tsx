import { getSessionUser } from "@buffetpro/auth";
import { redirect } from "next/navigation";
import type { Metadata } from "next";

export const metadata: Metadata = { title: "Dashboard" };

export default async function DashboardPage() {
  const user = await getSessionUser();

  if (!user) {
    redirect("/login");
  }

  return (
    <main className="min-h-screen bg-gray-50 p-8">
      <div className="mx-auto max-w-7xl">
        <h1 className="text-2xl font-bold text-gray-900">
          Welcome back, {user.name} 👋
        </h1>
        <p className="mt-1 text-sm text-gray-500">
          {user.hotelSlug} · {user.role}
        </p>

        <div className="mt-8 grid gap-6 md:grid-cols-3">
          <DashboardCard
            title="Active Buffers"
            value="—"
            description="Buffers in progress today"
            icon="📋"
          />
          <DashboardCard
            title="Dishes"
            value="—"
            description="Items in your dish library"
            icon="🍽️"
          />
          <DashboardCard
            title="Monthly Cost"
            value="—"
            description="Budget utilisation this month"
            icon="💰"
          />
        </div>
      </div>
    </main>
  );
}

function DashboardCard({
  title,
  value,
  description,
  icon,
}: {
  title: string;
  value: string;
  description: string;
  icon: string;
}) {
  return (
    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
      <div className="flex items-center justify-between">
        <span className="text-sm font-medium text-gray-500">{title}</span>
        <span className="text-2xl">{icon}</span>
      </div>
      <p className="mt-2 text-3xl font-bold text-gray-900">{value}</p>
      <p className="mt-1 text-xs text-gray-500">{description}</p>
    </div>
  );
}
