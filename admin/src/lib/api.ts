const rawBaseUrl =
  import.meta.env.VITE_API_BASE_URL || "http://localhost/shopittplus-api";

export const API_BASE_URL = rawBaseUrl.replace(/\/+$/, "");

export const apiUrl = (path: string) => {
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  return `${API_BASE_URL}${normalizedPath}`;
};
