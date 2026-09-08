import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",

  // M7: Security headers — CSP + frame protection + nosniff + referrer policy.
  async headers() {
    const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:4000";
    const apiDocTargets = [
      process.env.NEXT_PUBLIC_GATEWAY_URL || apiBaseUrl,
      process.env.NEXT_PUBLIC_CENTRAL_BANK_URL || "http://localhost:3000",
      process.env.NEXT_PUBLIC_WALLET_URL || "http://localhost:6969",
      process.env.NEXT_PUBLIC_CONNECTOR_URL || "http://localhost:5000",
    ].join(" ");
    return [
      {
        source: "/(.*)",
        headers: [
          { key: "X-Frame-Options", value: "DENY" },
          { key: "X-Content-Type-Options", value: "nosniff" },
          { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
          {
            key: "Content-Security-Policy",
            value:
              "default-src 'self';" +
              // unsafe-eval needed for Next.js dev Fast Refresh; unsafe-inline for dev inline scripts
              "script-src 'self' 'unsafe-eval' 'unsafe-inline';" +
              "style-src 'self' 'unsafe-inline';" +
              "img-src 'self' data:;" +
              `connect-src 'self' ${apiDocTargets};` +
              "frame-ancestors 'none';",
          },
        ],
      },
    ];
  },
};

export default nextConfig;
