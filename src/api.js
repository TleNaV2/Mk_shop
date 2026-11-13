const apiBaseUrl =
  process.env.NODE_ENV === "development"
    ? "/php_api/"
    : new URL("../php_api/", `${window.location.origin}${process.env.BASE_URL}`).toString();

export const apiUrl = (endpoint) => `${apiBaseUrl}${endpoint}`;
export const uploadUrl = (filename) =>
  apiUrl(`uploads/${encodeURIComponent(filename)}`);
