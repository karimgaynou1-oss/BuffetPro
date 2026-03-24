import { z } from "zod";
import { router, protectedProcedure } from "../trpc";
import { TRPCError } from "@trpc/server";

export const buffersRouter = router({
  list: protectedProcedure.query(async ({ ctx }) => {
    const { hotelId } = ctx.session.user;
    return ctx.prisma.buffer.findMany({
      where: { hotelId },
      include: {
        chef: { select: { id: true, name: true, email: true } },
        coordinator: { select: { id: true, name: true, email: true } },
        items: { include: { dish: true }, orderBy: { sortOrder: "asc" } },
      },
      orderBy: { eventDate: "desc" },
    });
  }),

  getById: protectedProcedure
    .input(z.object({ id: z.string() }))
    .query(async ({ ctx, input }) => {
      const { hotelId } = ctx.session.user;
      const buffer = await ctx.prisma.buffer.findFirst({
        where: { id: input.id, hotelId },
        include: {
          chef: true,
          coordinator: true,
          items: {
            include: { dish: { include: { allergens: { include: { allergen: true } } } } },
            orderBy: { sortOrder: "asc" },
          },
        },
      });
      if (!buffer) throw new TRPCError({ code: "NOT_FOUND" });
      return buffer;
    }),

  create: protectedProcedure
    .input(
      z.object({
        name: z.string().min(1),
        eventDate: z.date(),
        guestCount: z.number().int().positive(),
        notes: z.string().optional(),
        items: z.array(
          z.object({
            dishId: z.string(),
            quantity: z.number().int().positive(),
            portionCount: z.number().int().min(0),
            notes: z.string().optional(),
            sortOrder: z.number().int().default(0),
          })
        ),
      })
    )
    .mutation(async ({ ctx, input }) => {
      const { hotelId, id: chefId } = ctx.session.user;
      const { items, ...rest } = input;

      return ctx.prisma.buffer.create({
        data: {
          ...rest,
          hotelId,
          chefId,
          items: {
            create: items.map((item) => ({
              dishId: item.dishId,
              quantity: item.quantity,
              portionCount: item.portionCount,
              notes: item.notes,
              sortOrder: item.sortOrder,
            })),
          },
        },
        include: { items: { include: { dish: true } } },
      });
    }),

  updateStatus: protectedProcedure
    .input(
      z.object({
        id: z.string(),
        status: z.enum(["DRAFT", "PUBLISHED", "RECEIVED", "CUSTOMIZED", "FINALIZED"]),
      })
    )
    .mutation(async ({ ctx, input }) => {
      const { hotelId } = ctx.session.user;
      const existing = await ctx.prisma.buffer.findFirst({
        where: { id: input.id, hotelId },
      });
      if (!existing) throw new TRPCError({ code: "NOT_FOUND" });
      return ctx.prisma.buffer.update({
        where: { id: input.id },
        data: { status: input.status },
      });
    }),
});
