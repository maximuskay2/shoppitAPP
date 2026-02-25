import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";

// Local dev: proxy to local WAMP. Production build: not used (api.ts uses full URLs).
const LOCAL_API = process.env.VITE_API_BASE_URL || "http://localhost/shopittplus-api/public";

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    proxy: {
      // Fallback: if api.ts uses same-origin /api, proxy to local API (dev only).
      "/api": {
        target: LOCAL_API.replace(/\/+$/, ""),
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
