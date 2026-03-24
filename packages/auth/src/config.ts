import { type NextAuthOptions } from "next-auth";
import CredentialsProvider from "next-auth/providers/credentials";
import { prisma } from "@buffetpro/db";
import { createHash } from "crypto";
import type { UserRole } from "@buffetpro/types";

/**
 * Verify a plain-text password against the stored SHA-256 hash.
 *
 * ⚠️  This uses SHA-256 for simplicity in the scaffold.
 * Before going to production, replace this with bcrypt or argon2:
 *   npm install bcryptjs
 *   import { compare } from "bcryptjs";
 *   return compare(plain, stored);
 */
function verifyPassword(plain: string, stored: string): boolean {
  const hashed = createHash("sha256").update(plain).digest("hex");
  return hashed === stored;
}

export const authOptions: NextAuthOptions = {
  session: {
    strategy: "jwt",
    maxAge: 30 * 24 * 60 * 60, // 30 days
  },

  jwt: {
    maxAge: 30 * 24 * 60 * 60,
  },

  pages: {
    signIn: "/login",
    error: "/login",
  },

  providers: [
    CredentialsProvider({
      name: "credentials",
      credentials: {
        email: { label: "Email", type: "email" },
        password: { label: "Password", type: "password" },
        hotelSlug: { label: "Hotel", type: "text" },
      },

      async authorize(credentials) {
        if (!credentials?.email || !credentials.password || !credentials.hotelSlug) {
          return null;
        }

        // Resolve the hotel by slug to enforce tenant isolation
        const hotel = await prisma.hotel.findUnique({
          where: { slug: credentials.hotelSlug },
        });

        if (!hotel) return null;

        const user = await prisma.user.findUnique({
          where: {
            hotelId_email: {
              hotelId: hotel.id,
              email: credentials.email.toLowerCase(),
            },
          },
        });

        if (!user || !user.isActive) return null;

        const valid = verifyPassword(credentials.password, user.password);
        if (!valid) return null;

        return {
          id: user.id,
          email: user.email,
          name: user.name,
          role: user.role as UserRole,
          hotelId: user.hotelId,
          hotelSlug: hotel.slug,
        };
      },
    }),
  ],

  callbacks: {
    async jwt({ token, user }) {
      if (user) {
        token.id = user.id;
        token.role = (user as any).role;
        token.hotelId = (user as any).hotelId;
        token.hotelSlug = (user as any).hotelSlug;
      }
      return token;
    },

    async session({ session, token }) {
      if (session.user) {
        session.user.id = token.id as string;
        (session.user as any).role = token.role;
        (session.user as any).hotelId = token.hotelId;
        (session.user as any).hotelSlug = token.hotelSlug;
      }
      return session;
    },
  },
};
