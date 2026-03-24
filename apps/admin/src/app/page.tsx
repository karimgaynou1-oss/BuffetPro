import type { Metadata } from "next";
import { getSessionUser, requireRole } from "@buffetpro/auth";
import { UserRole } from "@buffetpro/types";
import { redirect } from "next/navigation";

export const metadata: Metadata = {
  title: "BuffetPro — Super Admin",
};

export default async function AdminDashboardPage() {
  const user = await getSessionUser();
  if (!user) redirect("/login");
  requireRole(user, UserRole.SUPER_ADMIN);

  return (
    <main className="min-h-screen bg-gray-900 p-8 text-white">
      <div className="mx-auto max-w-7xl">
        <h1 className="text-2xl font-bold">Super Admin Panel</h1>
        <p className="mt-1 text-sm text-gray-400">Manage all hotels, billing, and platform settings</p>

        <div className="mt-8 grid gap-6 md:grid-cols-4">
          {[
            { label: "Total Hotels", value: "—", icon: "🏨" },
            { label: "Active Subscriptions", value: "—", icon: "💳" },
            { label: "Monthly Revenue", value: "—", icon: "💰" },
            { label: "Total Users", value: "—", icon: "👥" },
          ].map((stat) => (
            <div
              key={stat.label}
              className="rounded-xl border border-gray-700 bg-gray-800 p-6"
            >
              <span className="text-2xl">{stat.icon}</span>
              <p className="mt-3 text-2xl font-bold">{stat.value}</p>
              <p className="mt-1 text-sm text-gray-400">{stat.label}</p>
            </div>
          ))}
        </div>

        <div className="mt-8 grid gap-6 md:grid-cols-2">
          <div className="rounded-xl border border-gray-700 bg-gray-800 p-6">
            <h2 className="font-semibold text-gray-100">Hotels</h2>
            <p className="mt-4 text-sm text-gray-400">No hotels registered yet.</p>
          </div>
          <div className="rounded-xl border border-gray-700 bg-gray-800 p-6">
            <h2 className="font-semibold text-gray-100">Recent Activity</h2>
            <p className="mt-4 text-sm text-gray-400">No activity logs yet.</p>
          </div>
        </div>
      </div>
    </main>
  );
}
