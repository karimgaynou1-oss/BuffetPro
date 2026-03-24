/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  transpilePackages: ["@buffetpro/ui", "@buffetpro/auth", "@buffetpro/db", "@buffetpro/types"],
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "**.cloudinary.com" },
      { protocol: "https", hostname: "**.unsplash.com" },
    ],
  },
};

module.exports = nextConfig;
