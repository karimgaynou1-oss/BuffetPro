import { getSessionUser, requireRole } from "@buffetpro/auth";
import { UserRole } from "@buffetpro/types";
import { redirect } from "next/navigation";
import type { Metadata } from "next";

export const metadata: Metadata = { title: "Coordinator — Buffers" };

export default async function CoordinatorPage() {
  const user = await getSessionUser();

  if (!user) redirect("/login");

  requireRole(user, UserRole.COORDINATOR, UserRole.HOTEL_ADMIN, UserRole.SUPER_ADMIN);

  return (
    <main className="min-h-screen bg-gray-50 p-8">
      <div className="mx-auto max-w-7xl">
        <h1 className="text-2xl font-bold text-gray-900">Coordinator Module</h1>
        <p className="mt-1 text-sm text-gray-500">
          Receive published buffers, customise, and export production sheets
        </p>

        <div className="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <p className="text-center text-gray-400">
            No buffers received yet. Buffers published by chefs will appear here.
          </p>
        </div>
      </div>
    </main>
  );
}
