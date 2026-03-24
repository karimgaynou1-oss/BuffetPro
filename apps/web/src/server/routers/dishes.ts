import { z } from "zod";
import { router, protectedProcedure } from "../trpc";
import { TRPCError } from "@trpc/server";

export const dishesRouter = router({
  list: protectedProcedure.query(async ({ ctx }) => {
    const { hotelId } = ctx.session.user;
    return ctx.prisma.dish.findMany({
      where: { hotelId, isActive: true },
      include: { allergens: { include: { allergen: true } } },
      orderBy: { name: "asc" },
    });
  }),

  getById: protectedProcedure
    .input(z.object({ id: z.string() }))
    .query(async ({ ctx, input }) => {
      const { hotelId } = ctx.session.user;
      const dish = await ctx.prisma.dish.findFirst({
        where: { id: input.id, hotelId },
        include: { allergens: { include: { allergen: true } } },
      });
      if (!dish) throw new TRPCError({ code: "NOT_FOUND" });
      return dish;
    }),

  create: protectedProcedure
    .input(
      z.object({
        name: z.string().min(1),
        nameFr: z.string().optional(),
        nameEs: z.string().optional(),
        description: z.string().optional(),
        category: z.enum(["STARTER", "MAIN", "DESSERT", "BEVERAGE", "SIDE", "BREAD", "SALAD"]),
        unit: z.string().default("portion"),
        costPerUnit: z.number().positive(),
        portionSize: z.number().positive(),
        imageUrl: z.string().url().optional(),
        allergenIds: z.array(z.string()).optional(),
      })
    )
    .mutation(async ({ ctx, input }) => {
      const { hotelId } = ctx.session.user;
      const { allergenIds, ...rest } = input;

      return ctx.prisma.dish.create({
        data: {
          ...rest,
          hotelId,
          allergens: allergenIds?.length
            ? {
                create: allergenIds.map((allergenId) => ({ allergenId })),
              }
            : undefined,
        },
        include: { allergens: { include: { allergen: true } } },
      });
    }),

  update: protectedProcedure
    .input(
      z.object({
        id: z.string(),
        name: z.string().min(1).optional(),
        nameFr: z.string().optional(),
        nameEs: z.string().optional(),
        description: z.string().optional(),
        costPerUnit: z.number().positive().optional(),
        portionSize: z.number().positive().optional(),
        imageUrl: z.string().url().optional(),
        isActive: z.boolean().optional(),
      })
    )
    .mutation(async ({ ctx, input }) => {
      const { hotelId } = ctx.session.user;
      const { id, ...data } = input;

      // Verify ownership
      const existing = await ctx.prisma.dish.findFirst({ where: { id, hotelId } });
      if (!existing) throw new TRPCError({ code: "NOT_FOUND" });

      return ctx.prisma.dish.update({ where: { id }, data });
    }),

  delete: protectedProcedure
    .input(z.object({ id: z.string() }))
    .mutation(async ({ ctx, input }) => {
      const { hotelId } = ctx.session.user;
      const existing = await ctx.prisma.dish.findFirst({
        where: { id: input.id, hotelId },
      });
      if (!existing) throw new TRPCError({ code: "NOT_FOUND" });

      return ctx.prisma.dish.update({
        where: { id: input.id },
        data: { isActive: false },
      });
    }),
});
