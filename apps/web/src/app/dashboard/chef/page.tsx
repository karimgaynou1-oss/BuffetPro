import { getSessionUser, requireRole } from "@buffetpro/auth";
import { UserRole } from "@buffetpro/types";
import { redirect } from "next/navigation";
import type { Metadata } from "next";

export const metadata: Metadata = { title: "Chef — Buffers" };

export default async function ChefPage() {
  const user = await getSessionUser();

  if (!user) redirect("/login");

  requireRole(user, UserRole.CHEF, UserRole.HOTEL_ADMIN, UserRole.SUPER_ADMIN);

  return (
    <main className="min-h-screen bg-gray-50 p-8">
      <div className="mx-auto max-w-7xl">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Chef Module</h1>
            <p className="mt-1 text-sm text-gray-500">Compose and publish buffet menus</p>
          </div>
          <button className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
            + New Buffer
          </button>
        </div>

        <div className="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <p className="text-center text-gray-400">No buffers yet. Create your first buffer to get started.</p>
        </div>
      </div>
    </main>
  );
}
