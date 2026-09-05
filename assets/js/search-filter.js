/**
 * FINDLY — shared search/filter helpers.
 * Builds a consistent query string from a search form (keyword, category,
 * date, location) used across Browse Found Items and Manage lists.
 */
window.SearchFilter = (function () {
  function readForm(formEl) {
    var params = {};
    if (!formEl) return params;
    new FormData(formEl).forEach(function (value, key) {
      var v = String(value).trim();
      if (v !== "") {
        params[key] = v;
      }
    });
    return params;
  }

  function toQueryString(params) {
    var parts = [];
    if (!params) return "";
    Object.keys(params).forEach(function (k) {
      parts.push(encodeURIComponent(k) + "=" + encodeURIComponent(params[k]));
    });
    return parts.join("&");
  }

  function bind(formId, basePath, onResult) {
    var form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var params = readForm(form);
      var qs = toQueryString(params);
      var target = basePath + (basePath.indexOf("?") === -1 ? "?" : "&") + qs;
      if (typeof onResult === "function") onResult(target, params);
    });
    var reset = form.querySelector("[type=reset]");
    if (reset) {
      reset.addEventListener("click", function () {
        setTimeout(function () {
          if (typeof onResult === "function") onResult(basePath, {});
        }, 60);
      });
    }
  }

  async function loadCategories(selectEl, includeAll) {
    if (!selectEl) return;
    try {
      var data = await API.get("/api/categories");
      var cats = data && Array.isArray(data.categories) ? data.categories : [];
      selectEl.innerHTML =
        (includeAll ? '<option value="">All categories</option>' : '<option value="">Select category</option>') +
        cats
          .map(function (c) { return '<option value="' + c.categoryId + '">' + Auth.escapeHtml(c.categoryName) + "</option>"; })
          .join("");
    } catch (err) {
      selectEl.innerHTML = (includeAll ? '<option value="">All categories</option>' : '<option value="">Select category</option>');
    }
  }

  return {
    readForm: readForm,
    toQueryString: toQueryString,
    bind: bind,
    loadCategories: loadCategories,
  };
})();