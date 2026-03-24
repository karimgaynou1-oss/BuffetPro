import { initTRPC, TRPCError } from "@trpc/server";
import { getServerSession } from "next-auth";
import { authOptions } from "@buffetpro/auth";
import { prisma } from "@buffetpro/db";
import superjson from "superjson";
import type { UserRole } from "@buffetpro/types";

export type Context = {
  session: {
    user: {
      id: string;
      email: string;
      name: string;
      role: UserRole;
      hotelId: string;
      hotelSlug: string;
    };
  } | null;
  prisma: typeof prisma;
};

export async function createContext(): Promise<Context> {
  const session = await getServerSession(authOptions);
  return { session: session as Context["session"], prisma };
}

const t = initTRPC.context<Context>().create({
  transformer: superjson,
});

export const router = t.router;
export const publicProcedure = t.procedure;

export const protectedProcedure = t.procedure.use(({ ctx, next }) => {
  if (!ctx.session?.user) {
    throw new TRPCError({ code: "UNAUTHORIZED" });
  }
  return next({ ctx: { ...ctx, session: ctx.session } });
});
