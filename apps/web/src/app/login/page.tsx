import type { Metadata } from "next";

export const metadata: Metadata = { title: "Sign In" };

export default function LoginPage() {
  return (
    <main className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
      <div className="w-full max-w-md rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <div className="mb-8 text-center">
          <span className="text-3xl">🍽️</span>
          <h1 className="mt-2 text-2xl font-bold text-gray-900">Sign in to BuffetPro</h1>
          <p className="mt-1 text-sm text-gray-500">Enter your hotel, email, and password</p>
        </div>

        {/* LoginForm is a Client Component — see components/forms/LoginForm.tsx */}
        <LoginFormPlaceholder />
      </div>
    </main>
  );
}

/**
 * Placeholder rendered server-side.
 * Replace with <LoginForm /> once the Client Component is wired up.
 */
function LoginFormPlaceholder() {
  return (
    <form className="space-y-4" action="/api/auth/callback/credentials" method="POST">
      <div>
        <label className="block text-sm font-medium text-gray-700" htmlFor="hotelSlug">
          Hotel workspace
        </label>
        <input
          id="hotelSlug"
          name="hotelSlug"
          type="text"
          placeholder="demo-hotel"
          required
          className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
        />
      </div>
      <div>
        <label className="block text-sm font-medium text-gray-700" htmlFor="email">
          Email
        </label>
        <input
          id="email"
          name="email"
          type="email"
          autoComplete="email"
          required
          className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
        />
      </div>
      <div>
        <label className="block text-sm font-medium text-gray-700" htmlFor="password">
          Password
        </label>
        <input
          id="password"
          name="password"
          type="password"
          autoComplete="current-password"
          required
          className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
        />
      </div>
      <button
        type="submit"
        className="w-full rounded-md bg-brand-600 py-2 text-sm font-medium text-white hover:bg-brand-700"
      >
        Sign in
      </button>
    </form>
  );
}
