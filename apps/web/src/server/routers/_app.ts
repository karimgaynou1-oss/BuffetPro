import { router } from "../trpc";
import { dishesRouter } from "./dishes";
import { buffersRouter } from "./buffers";

export const appRouter = router({
  dishes: dishesRouter,
  buffers: buffersRouter,
});

export type AppRouter = typeof appRouter;
