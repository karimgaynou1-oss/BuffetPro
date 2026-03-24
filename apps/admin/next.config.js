/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  transpilePackages: ["@buffetpro/ui", "@buffetpro/auth", "@buffetpro/db", "@buffetpro/types"],
};

module.exports = nextConfig;
