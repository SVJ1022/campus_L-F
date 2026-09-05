/**
 * FINDLY — authentication helpers + shared layout rendering.
 * Injecting role-aware navbar/sidebar keeps every page consistent.
 */
window.getBasePath = function () {
  if (typeof window.BASE === "string" && window.BASE !== "") {
    return window.BASE.endsWith("/") ? window.BASE : window.BASE + "/";
  }
  var path = (location.pathname || "").replace(/\\/g, "/");
  if (path.indexOf("/student/") !== -1 || path.indexOf("/staff/") !== -1 || path.indexOf("/admin/") !== -1) {
    return "../";
  }
  return "";
};

window.BASE = window.getBasePath();

window.Auth = (function () {
  var currentUser = null;
  var readyWaiters = [];

  var NAV = {
    STUDENT: [
      { href: "dashboard.html", icon: "bi-grid-1x2", label: "Dashboard" },
      { href: "report-lost.html", icon: "bi-plus-circle", label: "Report Lost Item" },
      { href: "browse-found.html", icon: "bi-search", label: "Browse Found Items" },
      { href: "my-reports.html", icon: "bi-journal-text", label: "My Reports" },
      { href: "notifications.html", icon: "bi-bell", label: "Notifications" },
      { href: "profile.html", icon: "bi-person", label: "My Profile" },
    ],
    STAFF: [
      { href: "dashboard.html", icon: "bi-grid-1x2", label: "Dashboard" },
      { href: "log-found-item.html", icon: "bi-plus-circle", label: "Log Found Item" },
      { href: "manage-found-items.html", icon: "bi-archive", label: "Manage Found Items" },
      { href: "claim-and-handover.html", icon: "bi-hand-thumbs-up", label: "Claim & Handover" },
      { href: "notifications.html", icon: "bi-bell", label: "Notifications" },
      { href: "profile.html", icon: "bi-person", label: "My Profile" },
    ],
    ADMIN: [
      { href: "dashboard.html", icon: "bi-grid-1x2", label: "Dashboard" },
      { href: "moderate-posts.html", icon: "bi-check2-square", label: "Moderate Posts" },
      { href: "manage-handovers.html", icon: "bi-hand-thumbs-up", label: "Manage Handovers" },
      { href: "manage-categories.html", icon: "bi-tags", label: "Categories" },
      { href: "manage-users.html", icon: "bi-people", label: "Users" },
      { href: "reports-statistics.html", icon: "bi-bar-chart", label: "Statistics" },
      { href: "notifications.html", icon: "bi-bell", label: "Notifications" },
      { href: "profile.html", icon: "bi-person", label: "My Profile" },
    ],
  };

  function roleLabel(role) {
    return { STUDENT: "Student", STAFF: "Staff", ADMIN: "Administrator" }[role] || role || "";
  }

  function avatar(user) {
    var name = (user && user.name ? user.name : "?").trim();
    return name.charAt(0).toUpperCase() || "?";
  }

  function escapeHtml(s) {
    if (s == null) return "";
    return String(s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }

  function buildNavList() {
    if (!currentUser || !currentUser.role) return "";
    var items = NAV[currentUser.role] || [];
    return items
      .map(function (it) {
        return (
          '<a class="fin-nav-item" href="' + it.href + '" data-page="' + it.href.replace(".html", "") + '">' +
          '<i class="bi ' + it.icon + '"></i><span>' + escapeHtml(it.label) + "</span></a>"
        );
      })
      .join("");
  }

  function injectLayout(user) {
    if (!user || document.getElementById("finNavbar")) return;

    var base = getBasePath();
    var page = (document.body ? document.body.dataset.page : "") || "";
    var nameStr = (user.name || "User").trim();
    var firstName = nameStr.split(" ")[0] || "User";

    var navbarHtml =
      '<nav class="navbar fin-navbar fixed-top navbar-expand" id="finNavbar">' +
        '<div class="container-fluid px-md-4">' +
          '<button class="btn btn-link text-muted d-lg-none p-1 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#finOffcanvas"><i class="bi bi-list fs-3"></i></button>' +
          '<a class="navbar-brand d-flex align-items-center gap-2 me-0" href="dashboard.html">' +
            '<img src="' + base + 'assets/img/logo.png" alt="FINDLY" width="34" height="34" class="rounded">' +
            '<span class="fin-brand">FINDLY</span>' +
            '<span class="fin-brand-sub d-none d-sm-inline">Campus Lost &amp; Found</span>' +
          "</a>" +
          '<div class="d-flex align-items-center gap-2 ms-auto">' +
            '<div class="dropdown">' +
              '<button class="btn btn-icon" data-bs-toggle="dropdown" aria-label="Notifications">' +
                '<i class="bi bi-bell"></i>' +
                '<span class="fin-notif-badge" id="notifBadge" style="display:none">0</span>' +
              "</button>" +
              '<div class="dropdown-menu dropdown-menu-end fin-notif-drop">' +
                '<div class="px-3 py-2 fw-semibold border-bottom">Notifications</div>' +
                '<div id="notifDropList" class="fin-notif-list"><div class="px-3 py-3 small text-muted">Loading…</div></div>' +
                '<div class="border-top"><a class="dropdown-item small" href="notifications.html"><i class="bi bi-arrow-right me-1"></i>View all</a></div>' +
              "</div>" +
            "</div>" +
            '<div class="dropdown">' +
              '<button class="btn btn-role d-flex align-items-center gap-2" data-bs-toggle="dropdown">' +
                '<span class="fin-avatar">' + avatar(user) + "</span>" +
                '<span class="d-none d-md-inline fin-nav-name">' + escapeHtml(firstName) + "</span>" +
                '<i class="bi bi-chevron-down d-none d-md-inline small"></i>' +
              "</button>" +
              '<ul class="dropdown-menu dropdown-menu-end">' +
                '<li><span class="dropdown-item-text fw-semibold">' + escapeHtml(user.name) + "</span></li>" +
                '<li><span class="dropdown-item-text small text-muted">' + escapeHtml(user.email) + "</span></li>" +
                '<li><span class="dropdown-item-text"><span class="badge fin-badge-role">' + escapeHtml(roleLabel(user.role)) + "</span></span></li>" +
                '<li><hr class="dropdown-divider"></li>' +
                '<li><a class="dropdown-item" href="profile.html"><i class="bi bi-person me-2"></i>My Profile</a></li>' +
                '<li><a class="dropdown-item text-danger" href="#" id="logoutBtn"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>' +
              "</ul>" +
            "</div>" +
          "</div>" +
        "</div>" +
      "</nav>" +
      '<div class="offcanvas offcanvas-start fin-offcanvas" id="finOffcanvas" tabindex="-1">' +
        '<div class="offcanvas-header border-bottom">' +
          '<span class="fin-brand">FINDLY</span>' +
          '<button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>' +
        "</div>" +
        '<div class="offcanvas-body p-0" id="finOffcanvasNav"></div>' +
      "</div>" +
      '<aside class="fin-sidenav d-none d-lg-block" id="finSidenav">' +
        '<nav class="fin-sidenav-nav" id="finSidenavNav"></nav>' +
        '<div class="fin-sidenav-foot">FINDLY v1.0</div>' +
      "</aside>";

    document.body.insertAdjacentHTML("afterbegin", navbarHtml);

    var inner = buildNavList();
    var sideNav = document.getElementById("finSidenavNav");
    if (sideNav) {
      sideNav.innerHTML = '<div class="fin-sidenav-title px-3 mb-2">' + escapeHtml(roleLabel(user.role)) + " Portal</div>" + inner;
    }
    var offNav = document.getElementById("finOffcanvasNav");
    if (offNav) {
      offNav.innerHTML = inner;
    }

    // Highlight the current page.
    document.querySelectorAll(".fin-nav-item").forEach(function (a) {
      if (page && a.getAttribute("data-page") === page) a.classList.add("active");
    });

    var logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
      logoutBtn.addEventListener("click", async function (e) {
        e.preventDefault();
        try { await API.post("/api/auth/logout"); } catch (_) {}
        location.href = getBasePath() + "login.html";
      });
    }

    document.body.classList.add("fin-body-inner");
  }

  async function guard(roles) {
    try {
      currentUser = await API.get("/api/auth/me");
    } catch (err) {
      var isAuthPage = location.pathname.endsWith("login.html") || location.pathname.endsWith("register.html");
      if (err.status === 401 && !isAuthPage) {
        location.href = getBasePath() + "login.html";
      }
      throw err;
    }
    if (!currentUser || !currentUser.role) {
      location.href = getBasePath() + "login.html";
      return null;
    }
    if (roles && roles.indexOf(currentUser.role) === -1) {
      var map = { STUDENT: "student/dashboard.html", STAFF: "staff/dashboard.html", ADMIN: "admin/dashboard.html" };
      var target = map[currentUser.role] || "login.html";
      location.href = getBasePath() + target;
      return currentUser;
    }
    injectLayout(currentUser);
    if (typeof window.Notifications !== "undefined") window.Notifications.refreshBadge();
    notifyReady();
    return currentUser;
  }

  function onReady(fn) {
    if (currentUser) { fn(currentUser); } else { readyWaiters.push(fn); }
  }

  function notifyReady() {
    readyWaiters.forEach(function (fn) { try { fn(currentUser); } catch (_) {} });
    readyWaiters = [];
  }

  return {
    user: function () { return currentUser; },
    guard: guard,
    onReady: onReady,
    roleLabel: roleLabel,
    escapeHtml: escapeHtml,
    init: function (roles) { return guard(roles); },
  };
})();

window.showToast = function (message, type) {
  type = type || "success";
  var icons = { success: "bi-check-circle", danger: "bi-x-circle", warning: "bi-exclamation-triangle", info: "bi-info-circle" };
  var container = document.querySelector(".fin-toasts");
  if (!container) {
    container = document.createElement("div");
    container.className = "fin-toasts";
    document.body.appendChild(container);
  }
  var colors = { success: "#16a34a", danger: "#dc2626", warning: "#f59e0b", info: "#0ea5e9" };
  var el = document.createElement("div");
  el.className = "fin-toast";
  el.innerHTML =
    '<div class="fin-toast-icon" style="color:' + (colors[type] || colors.success) + '"><i class="bi ' + (icons[type] || icons.success) + '"></i></div>' +
    '<div class="fin-toast-msg">' + Auth.escapeHtml(message) + "</div>";
  container.appendChild(el);
  setTimeout(function () { el.classList.add("show"); }, 10);
  setTimeout(function () {
    el.classList.remove("show");
    setTimeout(function () { el.remove(); }, 300);
  }, 3500);
};

window.formatDateTime = function (ts) {
  if (!ts) return "—";
  var d = new Date(String(ts).replace(" ", "T"));
  if (isNaN(d.getTime())) return Auth.escapeHtml(ts);
  return d.toLocaleString();
};

// Shared status/type badge + item card helpers.
window.ItemUI = (function () {
  var BADGES = {
    PENDING: '<span class="badge fin-badge fin-badge-pending">Pending</span>',
    ACTIVE: '<span class="badge fin-badge fin-badge-active">Active</span>',
    CLAIMED: '<span class="badge fin-badge fin-badge-claimed">Claimed</span>',
    RESOLVED: '<span class="badge fin-badge fin-badge-resolved">Resolved</span>',
    REJECTED: '<span class="badge fin-badge fin-badge-rejected">Rejected</span>',
  };
  var TYPES = {
    FOUND: '<span class="badge fin-badge fin-badge-found"><i class="bi bi-check2-circle me-1"></i>Found</span>',
    LOST: '<span class="badge fin-badge fin-badge-lost"><i class="bi bi-search me-1"></i>Lost</span>',
  };
  return {
    statusBadge: function (s) { return BADGES[s] || '<span class="badge fin-badge text-bg-secondary">' + Auth.escapeHtml(s) + "</span>"; },
    typeBadge: function (t) { return TYPES[t] || TYPES.LOST; },
    image: function (item) {
      return item && item.imageUrl
        ? API.uploadUrl(item.imageUrl)
        : getBasePath() + "assets/img/placeholder-item.png";
    },
    shortDate: function (d) {
      if (!d) return "—";
      var dt = new Date(String(d).replace(" ", "T"));
      return isNaN(dt.getTime()) ? Auth.escapeHtml(d) : dt.toLocaleDateString(undefined, { day: "numeric", month: "short", year: "numeric" });
    },
  };
})();

window.loadingState = function (container, on) {
  if (!container) return;
  var el = container.querySelector(".fin-empty");
  if (el) {
    el.style.display = on ? "block" : "none";
    if (on) {
      el.classList.remove("fin-empty-error");
      el.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Loading…';
    }
  }
};