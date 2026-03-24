import Link from "next/link";
import { Button } from "@buffetpro/ui";

export default function HomePage() {
  return (
    <main className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
      {/* Navigation */}
      <nav className="border-b border-gray-200 bg-white">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
          <span className="text-xl font-bold text-brand-600">🍽️ BuffetPro</span>
          <div className="flex items-center gap-4">
            <Link href="/pricing" className="text-sm text-gray-600 hover:text-gray-900">
              Pricing
            </Link>
            <Link href="/login">
              <Button variant="outline" size="sm">
                Log in
              </Button>
            </Link>
            <Link href="/register">
              <Button size="sm">Get started</Button>
            </Link>
          </div>
        </div>
      </nav>

      {/* Hero */}
      <section className="mx-auto max-w-7xl px-6 py-24 text-center">
        <h1 className="text-5xl font-extrabold tracking-tight text-gray-900">
          Buffet management,{" "}
          <span className="text-brand-600">elevated</span>
        </h1>
        <p className="mx-auto mt-6 max-w-2xl text-lg text-gray-500">
          BuffetPro connects your chefs and coordinators in real time — dish libraries, live
          buffers, cost tracking, and AI-powered menu suggestions all in one platform.
        </p>
        <div className="mt-10 flex justify-center gap-4">
          <Link href="/register">
            <Button size="lg">Start free trial</Button>
          </Link>
          <Link href="/pricing">
            <Button variant="outline" size="lg">
              View pricing
            </Button>
          </Link>
        </div>
      </section>

      {/* Feature highlights */}
      <section className="mx-auto max-w-7xl px-6 pb-24">
        <div className="grid gap-8 md:grid-cols-3">
          {[
            {
              icon: "👨‍🍳",
              title: "Chef Module",
              desc: "Build dish libraries, compose buffers, and publish to coordinators in one click.",
            },
            {
              icon: "📋",
              title: "Coordinator Module",
              desc: "Receive buffers, customise portions, print name tags & production sheets instantly.",
            },
            {
              icon: "💰",
              title: "Cost Engine",
              desc: "Track cost per portion, budget vs actual, and break down by category in real time.",
            },
          ].map((f) => (
            <div
              key={f.title}
              className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
              <div className="mb-4 text-3xl">{f.icon}</div>
              <h3 className="text-lg font-semibold text-gray-900">{f.title}</h3>
              <p className="mt-2 text-sm text-gray-500">{f.desc}</p>
            </div>
          ))}
        </div>
      </section>
    </main>
  );
}
