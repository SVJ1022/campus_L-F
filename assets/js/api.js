/**
 * FINDLY — single API wrapper.
 * Uses the native fetch API with session cookies (credentials: 'include').
 * Every endpoint returns the JSON envelope { success, data, message }.
 */
window.API = (function () {
  async function request(method, path, body, isForm) {
    const opts = { method: method, credentials: "include", headers: {} };

    if (body !== undefined && body !== null) {
      if (isForm) {
        opts.body = body; // FormData (do not set Content-Type)
      } else {
        opts.headers["Content-Type"] = "application/json";
        opts.body = JSON.stringify(body);
      }
    }

    let res;
    try {
      res = await fetch(window.API_BASE_URL + path, opts);
    } catch (err) {
      const e = new Error(
        "Cannot reach the server. Make sure XAMPP (Apache + MySQL) are running and the API URL in config/config.js is correct."
      );
      e.status = 0;
      throw e;
    }

    let payload = null;
    try {
      payload = await res.json();
    } catch (_) {
      /* non-JSON response */
    }

    if (res.status === 401) {
      // Session missing / expired -> send everyone to the login screen.
      const pathname = location.pathname;
      const isPublicPage = pathname.endsWith("login.html") ||
        pathname.endsWith("register.html") ||
        pathname.endsWith("index.html") ||
        pathname === "/" ||
        pathname.endsWith("/");
      if (!isPublicPage) {
        const redirect = encodeURIComponent(pathname + location.search);
        const base = typeof window.getBasePath === "function" ? window.getBasePath() : (window.BASE || "");
        location.href = base + "login.html?redirect=" + redirect;
      }
    }

    if (!res.ok) {
      const err = new Error((payload && payload.message) || "Request failed (HTTP " + res.status + ")");
      err.status = res.status;
      err.payload = payload || {};
      if (payload && payload.data && payload.data.errors) err.fieldErrors = payload.data.errors;
      throw err;
    }

    return payload ? payload.data : null;
  }

  return {
    get: function (path) { return request("GET", path); },
    post: function (path, body) { return request("POST", path, body); },
    put: function (path, body) { return request("PUT", path, body); },
    del: function (path) { return request("DELETE", path); },
    postForm: function (path, formData) { return request("POST", path, formData, true); },
    putForm: function (path, formData) { return request("PUT", path, formData, true); },
    uploadUrl: function (rel) {
      if (!rel) return "";
      if (rel.indexOf("http://") === 0 || rel.indexOf("https://") === 0) return rel;
      var cleaned = String(rel).replace(/^\/?(uploads\/)?/, "");
      return window.API_UPLOADS_URL + "/" + cleaned;
    },
  };
})();