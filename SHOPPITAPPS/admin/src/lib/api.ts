// Local dev (localhost:5173): use local WAMP API by default. Override with VITE_API_BASE_URL.
// Production build (railway up): use production API.
const explicit = (import.meta.env.VITE_API_BASE_URL ?? "").replace(/\/+$/, "");
const LOCAL_API = "http://localhost/shopittplus-api/public";
const PRODUCTION_API = "https://laravelapi-production-1ea4.up.railway.app";

export const API_BASE_URL = import.meta.env.DEV
  ? (explicit || LOCAL_API)
  : (explicit || PRODUCTION_API);

export const apiUrl = (path: string) => {
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  return `${API_BASE_URL}${normalizedPath}`;
};
