/**
 * FINDLY frontend configuration.
 * Point API_BASE_URL at the backend public directory.
 * In the standard XAMPP setup the backend lives at:
 *   http://localhost/findly/backend/public
 */
(function () {
  var hostname = location.hostname || "localhost";

  // --- Default (XAMPP: frontend and backend share the Apache origin) ---
  window.API_BASE_URL = "http://" + hostname + "/findly/backend/public";
  window.API_UPLOADS_URL = "http://" + hostname + "/findly/backend/uploads";

  // --- Optional local dev via PHP built-in server ---
  // If this page is being served from port 8080, talk to the local API instead.
  if (location.port === "8080") {
    window.API_BASE_URL = "http://" + hostname + ":8081";
    window.API_UPLOADS_URL = "http://" + hostname + ":8081/uploads";
  }

  window.API_BASE_PATH = "/api";
})();