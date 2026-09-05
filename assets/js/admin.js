/**
 * FINDLY — Admin area shared logic.
 */
window.Admin = (function () {
  function activityItem(a) {
    if (!a) return "";
    var icon = "bi-gear", bg = "var(--fin-indigo-soft)", color = "var(--fin-indigo)";
    var map = {
      POST: "bi-plus-circle", MODERATE: "bi-check2-square", UPDATE: "bi-pencil",
      UPDATE_CUSTODY: "bi-arrow-left-right", CLAIM: "bi-hand-thumbs-up",
      RECORD_RECIPIENT: "bi-person-check", RESOLVE: "bi-patch-check",
      CATEGORY_CHANGE: "bi-tags", USER_STATUS_CHANGE: "bi-person-dash",
    };
    var colors = {
      POST: ["var(--fin-success-bg)", "#15803d"], MODERATE: ["#e0e7ff", "#4338ca"],
      CLAIM: ["var(--fin-found-bg)", "#b45309"], RESOLVE: ["var(--fin-info-bg)", "#0369a1"],
      RECORD_RECIPIENT: ["var(--fin-success-bg)", "#15803d"],
      UPDATE_CUSTODY: ["var(--fin-warning-bg)", "#b45309"],
      USER_STATUS_CHANGE: ["var(--fin-danger-bg)", "#b91c1c"],
      CATEGORY_CHANGE: ["var(--fin-lost-bg)", "#475569"],
    };
    if (a.action && map[a.action]) { icon = map[a.action]; if (colors[a.action]) { bg = colors[a.action][0]; color = colors[a.action][1]; } }
    return (
      '<div class="fin-activity-item">' +
        '<div class="fin-activity-icon" style="background:' + bg + ";color:" + color + '"><i class="bi ' + icon + '"></i></div>' +
        '<div class="flex-grow-1">' +
          '<div class="fin-activity-text"><strong>' + Auth.escapeHtml(a.userName || "System") + "</strong> — " + Auth.escapeHtml(a.details || a.action || "") + "</div>" +
          '<div class="fin-activity-time">' + window.formatDateTime(a.timestamp) + (a.itemTitle ? " · " + Auth.escapeHtml(a.itemTitle) : "") + "</div>" +
        "</div>" +
      "</div>"
    );
  }

  return {
    activityItem: activityItem,
  };
})();