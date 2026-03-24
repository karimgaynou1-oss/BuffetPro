import { PrismaClient, UserRole, DishCategory, BufferStatus, SubscriptionPlan, SubscriptionStatus } from "@prisma/client";
import { createHash } from "crypto";

const prisma = new PrismaClient();

function hashPassword(password: string): string {
  // In production, use bcrypt/argon2. This is a dev seed only — never expose these credentials.
  return createHash("sha256").update(password).digest("hex");
}

async function main() {
  console.log("🌱 Seeding database...");

  // ── Allergens ────────────────────────────────────────────────────────────
  const allergens = await Promise.all([
    prisma.allergen.upsert({
      where: { code: "GLUTEN" },
      update: {},
      create: { code: "GLUTEN", label: "Gluten", labelFr: "Gluten", labelEs: "Gluten", icon: "🌾" },
    }),
    prisma.allergen.upsert({
      where: { code: "MILK" },
      update: {},
      create: { code: "MILK", label: "Milk", labelFr: "Lait", labelEs: "Leche", icon: "🥛" },
    }),
    prisma.allergen.upsert({
      where: { code: "EGGS" },
      update: {},
      create: { code: "EGGS", label: "Eggs", labelFr: "Œufs", labelEs: "Huevos", icon: "🥚" },
    }),
    prisma.allergen.upsert({
      where: { code: "NUTS" },
      update: {},
      create: { code: "NUTS", label: "Tree Nuts", labelFr: "Fruits à coque", labelEs: "Frutos secos", icon: "🥜" },
    }),
    prisma.allergen.upsert({
      where: { code: "SHELLFISH" },
      update: {},
      create: { code: "SHELLFISH", label: "Shellfish", labelFr: "Crustacés", labelEs: "Mariscos", icon: "🦐" },
    }),
  ]);

  console.log(`✅ Seeded ${allergens.length} allergens`);

  // ── Demo Hotel ───────────────────────────────────────────────────────────
  const hotel = await prisma.hotel.upsert({
    where: { slug: "demo-hotel" },
    update: {},
    create: {
      name: "Demo Hotel",
      slug: "demo-hotel",
      primaryColor: "#1a56db",
      timezone: "Europe/Paris",
      locale: "en",
    },
  });

  console.log(`✅ Seeded hotel: ${hotel.name}`);

  // ── Users ────────────────────────────────────────────────────────────────
  const adminUser = await prisma.user.upsert({
    where: { hotelId_email: { hotelId: hotel.id, email: "admin@demo-hotel.com" } },
    update: {},
    create: {
      hotelId: hotel.id,
      email: "admin@demo-hotel.com",
      name: "Hotel Admin",
      password: hashPassword("admin123"),
      role: UserRole.HOTEL_ADMIN,
    },
  });

  const chefUser = await prisma.user.upsert({
    where: { hotelId_email: { hotelId: hotel.id, email: "chef@demo-hotel.com" } },
    update: {},
    create: {
      hotelId: hotel.id,
      email: "chef@demo-hotel.com",
      name: "Head Chef",
      password: hashPassword("chef123"),
      role: UserRole.CHEF,
    },
  });

  const coordinatorUser = await prisma.user.upsert({
    where: { hotelId_email: { hotelId: hotel.id, email: "coordinator@demo-hotel.com" } },
    update: {},
    create: {
      hotelId: hotel.id,
      email: "coordinator@demo-hotel.com",
      name: "Event Coordinator",
      password: hashPassword("coordinator123"),
      role: UserRole.COORDINATOR,
    },
  });

  console.log(`✅ Seeded 3 users (admin, chef, coordinator)`);

  // ── Demo Subscription ────────────────────────────────────────────────────
  await prisma.subscription.upsert({
    where: { hotelId: hotel.id },
    update: {},
    create: {
      hotelId: hotel.id,
      stripeCustomerId: "cus_demo_hotel",
      stripeSubscriptionId: "sub_demo_hotel",
      stripePriceId: "price_demo_professional",
      plan: SubscriptionPlan.PROFESSIONAL,
      status: SubscriptionStatus.ACTIVE,
      currentPeriodStart: new Date(),
      currentPeriodEnd: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000),
    },
  });

  console.log(`✅ Seeded subscription`);

  // ── Dishes ───────────────────────────────────────────────────────────────
  const saladDish = await prisma.dish.upsert({
    where: { id: "seed-dish-1" },
    update: {},
    create: {
      id: "seed-dish-1",
      hotelId: hotel.id,
      name: "Caesar Salad",
      nameFr: "Salade César",
      nameEs: "Ensalada César",
      description: "Classic Caesar salad with romaine lettuce, croutons, and Parmesan",
      category: DishCategory.SALAD,
      unit: "portion",
      costPerUnit: 2.5,
      portionSize: 0.25,
      allergens: {
        create: [
          { allergenId: allergens.find(a => a.code === "GLUTEN")!.id },
          { allergenId: allergens.find(a => a.code === "MILK")!.id },
          { allergenId: allergens.find(a => a.code === "EGGS")!.id },
        ],
      },
    },
  });

  const mainDish = await prisma.dish.upsert({
    where: { id: "seed-dish-2" },
    update: {},
    create: {
      id: "seed-dish-2",
      hotelId: hotel.id,
      name: "Roasted Chicken Breast",
      nameFr: "Suprême de poulet rôti",
      nameEs: "Pechuga de pollo asada",
      description: "Herb-roasted chicken breast with jus",
      category: DishCategory.MAIN,
      unit: "piece",
      costPerUnit: 4.8,
      portionSize: 0.18,
    },
  });

  const dessertDish = await prisma.dish.upsert({
    where: { id: "seed-dish-3" },
    update: {},
    create: {
      id: "seed-dish-3",
      hotelId: hotel.id,
      name: "Chocolate Mousse",
      nameFr: "Mousse au chocolat",
      nameEs: "Mousse de chocolate",
      description: "Dark chocolate mousse served in a cup",
      category: DishCategory.DESSERT,
      unit: "cup",
      costPerUnit: 1.9,
      portionSize: 0.12,
      allergens: {
        create: [
          { allergenId: allergens.find(a => a.code === "MILK")!.id },
          { allergenId: allergens.find(a => a.code === "EGGS")!.id },
        ],
      },
    },
  });

  console.log(`✅ Seeded 3 dishes`);

  // ── Demo Buffer ──────────────────────────────────────────────────────────
  await prisma.buffer.upsert({
    where: { id: "seed-buffer-1" },
    update: {},
    create: {
      id: "seed-buffer-1",
      hotelId: hotel.id,
      chefId: chefUser.id,
      coordinatorId: coordinatorUser.id,
      name: "Saturday Evening Gala Buffet",
      eventDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000),
      guestCount: 120,
      status: BufferStatus.PUBLISHED,
      notes: "VIP event — halal options required for 20 guests",
      totalCost: 840.0,
      items: {
        create: [
          { dishId: saladDish.id, quantity: 3, portionCount: 120, sortOrder: 1 },
          { dishId: mainDish.id, quantity: 2, portionCount: 120, sortOrder: 2 },
          { dishId: dessertDish.id, quantity: 4, portionCount: 120, sortOrder: 3 },
        ],
      },
    },
  });

  console.log(`✅ Seeded demo buffer`);
  console.log("\n🎉 Database seeded successfully!");
  console.log("\n📋 Demo credentials:");
  console.log("   Admin:       admin@demo-hotel.com / admin123");
  console.log("   Chef:        chef@demo-hotel.com / chef123");
  console.log("   Coordinator: coordinator@demo-hotel.com / coordinator123");
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
