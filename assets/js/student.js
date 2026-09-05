/**
 * FINDLY — Student area shared logic.
 */
window.Student = (function () {
  function itemCard(item) {
    if (!item) return "";
    var itemId = encodeURIComponent(item.itemId || "");
    return (
      '<div class="col">' +
        '<div class="fin-item-card">' +
          '<a href="item-details.html?id=' + itemId + '">' +
            '<img src="' + ItemUI.image(item) + '" alt="' + Auth.escapeHtml(item.title) + '" class="fin-item-img" loading="lazy">' +
          "</a>" +
          '<div class="fin-item-body">' +
            '<div class="d-flex justify-content-between align-items-start gap-2">' +
              '<div class="fin-item-title"><a href="item-details.html?id=' + itemId + '" class="text-ink">' + Auth.escapeHtml(item.title) + "</a></div>" +
              ItemUI.statusBadge(item.status) +
            "</div>" +
            '<div class="fin-item-meta"><span><i class="bi bi-geo-alt me-1"></i>' + Auth.escapeHtml(item.location) + "</span>" +
              '<span><i class="bi bi-calendar3 me-1"></i>' + ItemUI.shortDate(item.itemDate) + "</span></div>" +
            '<div class="fin-item-desc">' + Auth.escapeHtml(item.description) + "</div>" +
            '<div class="mt-auto d-flex justify-content-between align-items-center pt-2">' +
              '<span class="badge fin-badge text-bg-light border">' + Auth.escapeHtml(item.categoryName || "General") + "</span>" +
              '<a class="small fw-semibold" href="item-details.html?id=' + itemId + '">View details <i class="bi bi-arrow-right"></i></a>' +
            "</div>" +
          "</div>" +
        "</div>" +
      "</div>"
    );
  }

  function emptyState(message) {
    return '<div class="fin-empty"><i class="bi bi-inbox"></i>' + Auth.escapeHtml(message || "No results found") + "</div>";
  }

  function loadingStateHTML() {
    return '<div class="fin-empty"><div class="spinner-border spinner-border-sm me-2 text-primary"></div>Loading…</div>';
  }

  return {
    itemCard: itemCard,
    emptyState: emptyState,
    loadingStateHTML: loadingStateHTML,
  };
})();