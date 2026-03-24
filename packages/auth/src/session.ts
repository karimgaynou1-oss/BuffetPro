import { getServerSession } from "next-auth";
import { authOptions } from "./config";
import type { UserRole, SessionUser } from "@buffetpro/types";

/**
 * Returns the authenticated session user, or null if not signed in.
 * Use this in Next.js Server Components and API routes.
 */
export async function getSessionUser(): Promise<SessionUser | null> {
  const session = await getServerSession(authOptions);
  if (!session?.user) return null;

  const u = session.user as any;
  return {
    id: u.id,
    email: u.email ?? "",
    name: u.name ?? "",
    role: u.role,
    hotelId: u.hotelId,
    hotelSlug: u.hotelSlug,
  };
}

/**
 * Throws a 401-like error if the user is not authenticated.
 * Useful in tRPC procedures.
 */
export async function requireAuth(): Promise<SessionUser> {
  const user = await getSessionUser();
  if (!user) throw new Error("UNAUTHORIZED");
  return user;
}

/**
 * Checks whether the session user has at least one of the required roles.
 */
export function hasRole(user: SessionUser, ...roles: UserRole[]): boolean {
  return roles.includes(user.role);
}

/**
 * Throws if the user doesn't have at least one of the required roles.
 */
export function requireRole(user: SessionUser, ...roles: UserRole[]): void {
  if (!hasRole(user, ...roles)) {
    throw new Error("FORBIDDEN");
  }
}
