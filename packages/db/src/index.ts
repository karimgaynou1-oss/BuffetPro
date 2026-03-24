export { PrismaClient } from "@prisma/client";
export * from "@prisma/client";

import { PrismaClient } from "@prisma/client";

declare global {
  // eslint-disable-next-line no-var
  var __prisma: PrismaClient | undefined;
}

/**
 * Singleton Prisma client.
 * In development, reuse a global instance to avoid exhausting DB connections
 * during hot-module reloads (Next.js dev mode).
 */
export function getPrismaClient(): PrismaClient {
  if (process.env.NODE_ENV === "production") {
    return new PrismaClient();
  }

  if (!global.__prisma) {
    global.__prisma = new PrismaClient({
      log: ["query", "warn", "error"],
    });
  }
  return global.__prisma;
}

export const prisma = getPrismaClient();
