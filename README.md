# BuffetPro

> A multi-tenant SaaS platform for hotel & restaurant buffet management — featuring chef coordination, cost tracking, and AI-assisted menu planning.

## Architecture

```
BuffetPro/
├── apps/
│   ├── web/          # Next.js 14 frontend + API (App Router)
│   └── admin/        # Super-admin dashboard
├── packages/
│   ├── db/           # Prisma ORM + MySQL schema
│   ├── auth/         # NextAuth.js utilities & RBAC
│   ├── types/        # Shared TypeScript types
│   └── ui/           # Shared Tailwind component library
├── turbo.json
├── pnpm-workspace.yaml
└── package.json
```

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | Next.js 14+ (App Router, React, TypeScript) |
| Styling | Tailwind CSS |
| ORM | Prisma |
| Database | MySQL 8+ |
| Auth | NextAuth.js (JWT) |
| Payments | Stripe |
| Monorepo | Turborepo + pnpm workspaces |
| Testing | Jest |
| API | tRPC |

## Quick Start

### Prerequisites
- Node.js ≥ 18
- pnpm ≥ 8
- MySQL 8+

### Installation

```bash
# Clone the repository
git clone https://github.com/karimgaynou1-oss/BuffetPro.git
cd BuffetPro

# Install dependencies
pnpm install

# Set up environment variables
cp .env.example .env
# Edit .env with your values

# Generate Prisma client and run migrations
pnpm db:generate
pnpm db:migrate

# Seed the database
pnpm db:seed

# Start development servers
pnpm dev
```

The web app will be available at `http://localhost:3000`.

## Environment Variables

See [`.env.example`](.env.example) for all required environment variables with documentation.

## Database Schema

Core tables (all scoped by `hotel_id` for multi-tenancy):

- **hotels** — tenant root record
- **users** — roles: `SUPER_ADMIN`, `HOTEL_ADMIN`, `CHEF`, `COORDINATOR`, `CLIENT`
- **dishes** — menu items with allergen tracking
- **dish_allergens** — many-to-many allergen relationships
- **buffers** — chef-composed buffet menus
- **buffer_items** — individual dishes within a buffer
- **subscriptions** — Stripe subscription records
- **audit_logs** — immutable change history

## Development Workflow

```bash
pnpm dev          # Start all apps in parallel
pnpm build        # Build all packages and apps
pnpm lint         # Lint all workspaces
pnpm test         # Run all tests
pnpm db:studio    # Open Prisma Studio
```

## Deployment

- **Frontend / API**: Vercel (`apps/web`)
- **Database**: MySQL on Hostinger (or PlanetScale)
- **Admin Panel**: Vercel (`apps/admin`)

## Roadmap

| Phase | Focus |
|---|---|
| Phase 1 – MVP | Auth, multi-tenant, Chef & Coordinator modules |
| Phase 2 – Cost | Cost engine, portions calculator, budget tracking |
| Phase 3 – AI | Auto-compose, translation API, menu suggestions |
| Phase 4 – Scale | Analytics, white-label, API for 3rd parties |

## License

MIT
