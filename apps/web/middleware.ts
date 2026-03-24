import { withAuth } from "next-auth/middleware";
import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

/**
 * Multi-tenant middleware.
 *
 * Strategy:
 * 1. Public routes (landing, auth, marketing) are always accessible.
 * 2. Dashboard routes require an authenticated session.
 * 3. The hotel slug is read from the session and injected as a request header
 *    (`x-hotel-id`, `x-hotel-slug`) so downstream Server Components and API
 *    routes can use it without another DB round-trip.
 */

const PUBLIC_PATHS = [
  "/",
  "/login",
  "/register",
  "/pricing",
  "/about",
  "/contact",
  "/api/auth",   // NextAuth internal routes
  "/api/stripe/webhook",
  "/_next",
  "/favicon.ico",
];

function isPublicPath(pathname: string): boolean {
  return PUBLIC_PATHS.some((p) => pathname === p || pathname.startsWith(p + "/"));
}

export default withAuth(
  function middleware(req: NextRequest) {
    const { pathname } = req.nextUrl;

    // Allow public paths through
    if (isPublicPath(pathname)) {
      return NextResponse.next();
    }

    // Inject hotel context headers from the JWT token
    const token = (req as any).nextauth?.token;
    if (token) {
      const requestHeaders = new Headers(req.headers);
      requestHeaders.set("x-hotel-id", token.hotelId as string);
      requestHeaders.set("x-hotel-slug", token.hotelSlug as string);
      requestHeaders.set("x-user-id", token.sub as string);
      requestHeaders.set("x-user-role", token.role as string);

      return NextResponse.next({ request: { headers: requestHeaders } });
    }

    return NextResponse.next();
  },
  {
    callbacks: {
      authorized({ token, req }) {
        const { pathname } = req.nextUrl;
        // Public routes are always authorized
        if (isPublicPath(pathname)) return true;
        // All other routes require a valid token
        return !!token;
      },
    },
  }
);

export const config = {
  matcher: [
    /*
     * Match all request paths except:
     * - _next/static (static files)
     * - _next/image (image optimisation)
     * - favicon.ico
     */
    "/((?!_next/static|_next/image|favicon.ico).*)",
  ],
};
