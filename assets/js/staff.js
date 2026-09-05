/**
 * FINDLY — Staff area shared logic (also reused by the Admin handover screen).
 */
window.Staff = (function () {
  var tableActionsBound = false;
  var activeReloadCallback = null;

  function custodyPill(status) {
    var map = { IN_CUSTODY: "In custody", MOVED: "Moved", HANDED_OVER: "Handed over" };
    var st = status || "IN_CUSTODY";
    return '<span class="fin-custody-pill ' + Auth.escapeHtml(st) + '">' + Auth.escapeHtml(map[st] || st) + "</span>";
  }

  /** Row for the manage-found-items / manage-handovers tables. */
  function row(item, showOwner) {
    if (!item) return "";
    var tr = '<tr>' +
      "<td>" +
        '<div class="d-flex align-items-center gap-2">' +
          '<img src="' + ItemUI.image(item) + '" class="fin-thumb" alt="">' +
          '<div><div class="fw-semibold">' + Auth.escapeHtml(item.title) + "</div>" +
          '<div class="small text-muted">' + ItemUI.shortDate(item.itemDate) + "</div></div></div></td>" +
      "<td>" + Auth.escapeHtml(item.categoryName || "—") + "</td>" +
      "<td>" + ItemUI.statusBadge(item.status) + "</td>" +
      "<td>" + custodyPill(item.custodyStatus) + '<div class="small text-muted">' + Auth.escapeHtml(item.custodyLocation || "—") + "</div></td>" +
      (showOwner ? '<td class="small">' + Auth.escapeHtml(item.postedByName || "—") + "</td>" : "") +
      "<td class='text-end whitespace-nowrap'>" + actions(item) + "</td>" +
    "</tr>";
    return tr;
  }

  function actions(item) {
    if (!item) return "";
    var s = item.status;
    var itemId = encodeURIComponent(item.itemId || "");
    var btns = "";
    if (s === "PENDING") {
      btns = '<span class="small text-muted">Awaiting approval</span>';
    }
    if (s === "ACTIVE") {
      btns =
        '<button class="btn btn-sm btn-soft mb-1 custody-btn" data-id="' + itemId + '"><i class="bi bi-arrow-left-right"></i> Custody</button> ' +
        '<button class="btn btn-sm btn-soft-warning mb-1 claim-btn" data-id="' + itemId + '"><i class="bi bi-hand-thumbs-up"></i> Claim</button>';
    }
    if (s === "CLAIMED") {
      btns =
        '<button class="btn btn-sm btn-soft-success mb-1 recipient-btn" data-id="' + itemId + '"><i class="bi bi-person-check"></i> Recipient</button> ' +
        '<button class="btn btn-sm btn-primary mb-1 resolve-btn" data-id="' + itemId + '"><i class="bi bi-check-lg"></i> Resolve</button>';
    }
    if (s === "RESOLVED") {
      btns = '<span class="small text-muted"><i class="bi bi-check-circle text-success"></i> Handed over</span>';
    }
    if (s === "REJECTED") {
      btns = '<span class="small text-muted">Rejected</span>';
    }
    return btns;
  }

  function claim(id) {
    return API.put("/api/items/" + id + "/claim");
  }
  function resolve(id) {
    return API.put("/api/items/" + id + "/resolve");
  }
  function custody(id, payload) {
    return API.put("/api/items/" + id + "/custody", payload);
  }
  function recipient(id, payload) {
    return API.put("/api/items/" + id + "/recipient", payload);
  }

  /** Lazily inject the four action modals (custody, claim, recipient, resolve). */
  function ensureModals() {
    if (document.getElementById("finCustodyModal")) return;
    var modals = "";
    modals +=
      '<div class="modal fade" id="finCustodyModal"><div class="modal-dialog"><div class="modal-content">' +
        '<div class="modal-header"><h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Update Custody</h5>' +
        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
        '<div class="modal-body">' +
          '<div class="mb-3"><label class="form-label">Custody status</label>' +
          '<select id="custodyStatusSel" class="form-select"><option value="IN_CUSTODY">In custody</option><option value="MOVED">Moved</option><option value="HANDED_OVER">Handed over</option></select></div>' +
          '<div class="mb-2"><label class="form-label">Custody location</label>' +
          '<input type="text" id="custodyLocationInput" class="form-control" maxlength="200"></div>' +
        "</div>" +
        '<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>' +
        '<button type="button" class="btn btn-primary" id="custodySaveBtn"><span class="spinner-border spinner-border-sm d-none me-2" id="custodySpinner"></span>Save</button></div>' +
      "</div></div></div>";

    modals +=
      '<div class="modal fade" id="finClaimModal"><div class="modal-dialog"><div class="modal-content">' +
        '<div class="modal-header"><h5 class="modal-title"><i class="bi bi-hand-thumbs-up me-2"></i>Mark as Claimed</h5>' +
        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
        '<div class="modal-body">' +
          '<div class="fin-verify-note mb-2"><i class="bi bi-info-circle me-1"></i>Only proceed if the owner has been verified <strong>in person</strong> at the desk.</div>' +
          '<div class="small text-muted">Item: <strong id="claimItemTitle"></strong>. After claiming you must record the recipient details.</div>' +
        "</div>" +
        '<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>' +
        '<button type="button" class="btn btn-soft-warning" id="claimSaveBtn"><span class="spinner-border spinner-border-sm d-none me-2" id="claimSpinner"></span>Confirm claim</button></div>' +
      "</div></div></div>";

    modals +=
      '<div class="modal fade" id="finRecipientModal"><div class="modal-dialog"><div class="modal-content">' +
        '<div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-check me-2"></i>Record Recipient</h5>' +
        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
        '<div class="modal-body">' +
          '<div class="small text-muted mb-3">Verified owner details for <strong id="recipientItemTitle"></strong>. Recorded privately, never shown to students online.</div>' +
          '<div class="mb-2"><label class="form-label">Recipient full name</label>' +
          '<input type="text" id="recipientNameInput" class="form-control" maxlength="100"></div>' +
          '<div class="mb-2"><label class="form-label">Contact number</label>' +
          '<input type="tel" id="recipientContactInput" class="form-control" maxlength="15"></div>' +
          '<div class="mb-2"><label class="form-label">Enrollment / Employee number</label>' +
          '<input type="text" id="recipientEnrollInput" class="form-control" maxlength="30"></div>' +
        "</div>" +
        '<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>' +
        '<button type="button" class="btn btn-soft-success" id="recipientSaveBtn"><span class="spinner-border spinner-border-sm d-none me-2" id="recipientSpinner"></span>Save recipient</button></div>' +
      "</div></div></div>";

    modals +=
      '<div class="modal fade" id="finResolveModal"><div class="modal-dialog"><div class="modal-content">' +
        '<div class="modal-header"><h5 class="modal-title"><i class="bi bi-check-lg me-2"></i>Resolve Item</h5>' +
        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
        '<div class="modal-body">' +
          '<div class="small text-muted">Confirm the handover of <strong id="resolveItemTitle"></strong>. This closes the record.</div>' +
          '<div id="resolveSummary" class="small mt-2 bg-light rounded-3 p-2"></div>' +
        "</div>" +
        '<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>' +
        '<button type="button" class="btn btn-primary" id="resolveSaveBtn"><span class="spinner-border spinner-border-sm d-none me-2" id="resolveSpinner"></span>Mark resolved</button></div>' +
      "</div></div></div>";

    document.body.insertAdjacentHTML("beforeend", modals);
  }

  /** Delegate clicks from item action buttons to the modals without duplicate listener registration. */
  function bindTableActions(reload) {
    ensureModals();
    activeReloadCallback = reload;

    if (tableActionsBound) return;
    tableActionsBound = true;

    document.addEventListener("click", function (e) {
      var custodyBtn = e.target.closest(".custody-btn");
      if (custodyBtn) {
        var id = Number(custodyBtn.dataset.id);
        API.get("/api/items/" + id)
          .then(function (d) {
            $("#custodyLocationInput").val((d.item && d.item.custodyLocation) || "");
            var bi = window.bootstrap.Modal.getOrCreateInstance(document.getElementById("finCustodyModal"));
            document.getElementById("custodySaveBtn").onclick = async function () {
              var btn = this, sp = document.getElementById("custodySpinner");
              sp.classList.remove("d-none"); btn.disabled = true;
              try {
                await Staff.custody(id, {
                  custodyLocation: $("#custodyLocationInput").val().trim(),
                  custodyStatus: $("#custodyStatusSel").val(),
                });
                window.showToast("Custody updated.");
                bi.hide();
                if (typeof activeReloadCallback === "function") activeReloadCallback();
              } catch (err) { window.showToast(err.message || "Failed to update custody", "danger"); }
              sp.classList.add("d-none"); btn.disabled = false;
            };
            bi.show();
          })
          .catch(function (err) {
            window.showToast(err.message || "Failed to fetch item details", "danger");
          });
        return;
      }
      var claimBtn = e.target.closest(".claim-btn");
      if (claimBtn) {
        var cid = Number(claimBtn.dataset.id);
        API.get("/api/items/" + cid)
          .then(function (d) {
            document.getElementById("claimItemTitle").textContent = (d.item && d.item.title) || "";
            var bi = window.bootstrap.Modal.getOrCreateInstance(document.getElementById("finClaimModal"));
            document.getElementById("claimSaveBtn").onclick = async function () {
              var btn = this, sp = document.getElementById("claimSpinner");
              sp.classList.remove("d-none"); btn.disabled = true;
              try {
                await Staff.claim(cid);
                window.showToast("Item claimed. Now record the recipient.", "info");
                bi.hide();
                if (typeof activeReloadCallback === "function") activeReloadCallback();
              } catch (err) { window.showToast(err.message || "Failed to claim item", "danger"); }
              sp.classList.add("d-none"); btn.disabled = false;
            };
            bi.show();
          })
          .catch(function (err) {
            window.showToast(err.message || "Failed to fetch item details", "danger");
          });
        return;
      }
      var recipientBtn = e.target.closest(".recipient-btn");
      if (recipientBtn) {
        var rid = Number(recipientBtn.dataset.id);
        API.get("/api/items/" + rid)
          .then(function (d) {
            var item = (d && d.item) || {};
            document.getElementById("recipientItemTitle").textContent = item.title || "";
            $("#recipientNameInput").val(item.recipientName || "");
            $("#recipientContactInput").val(item.recipientContactNo || "");
            $("#recipientEnrollInput").val(item.recipientEnrollmentNo || "");
            var bi = window.bootstrap.Modal.getOrCreateInstance(document.getElementById("finRecipientModal"));
            document.getElementById("recipientSaveBtn").onclick = async function () {
              var btn = this, sp = document.getElementById("recipientSpinner");
              sp.classList.remove("d-none"); btn.disabled = true;
              try {
                await Staff.recipient(rid, {
                  recipientName: $("#recipientNameInput").val().trim(),
                  recipientContactNo: $("#recipientContactInput").val().trim(),
                  recipientEnrollmentNo: $("#recipientEnrollInput").val().trim(),
                });
                window.showToast("Recipient recorded. Ready to resolve.");
                bi.hide();
                if (typeof activeReloadCallback === "function") activeReloadCallback();
              } catch (err) {
                var msg = err.fieldErrors ? Object.values(err.fieldErrors).join(" · ") : (err.message || "Failed to record recipient");
                window.showToast(msg, "danger");
              }
              sp.classList.add("d-none"); btn.disabled = false;
            };
            bi.show();
          })
          .catch(function (err) {
            window.showToast(err.message || "Failed to fetch item details", "danger");
          });
        return;
      }
      var resolveBtn = e.target.closest(".resolve-btn");
      if (resolveBtn) {
        var sid = Number(resolveBtn.dataset.id);
        API.get("/api/items/" + sid)
          .then(function (d) {
            var it = (d && d.item) || {};
            document.getElementById("resolveItemTitle").textContent = it.title || "";
            document.getElementById("resolveSummary").innerHTML =
              "Recipient: <strong>" + Auth.escapeHtml(it.recipientName || "—") + "</strong> (" +
              Auth.escapeHtml(it.recipientContactNo || "—") + ") · " +
              Auth.escapeHtml(it.recipientEnrollmentNo || "—");
            var bi = window.bootstrap.Modal.getOrCreateInstance(document.getElementById("finResolveModal"));
            document.getElementById("resolveSaveBtn").onclick = async function () {
              var btn = this, sp = document.getElementById("resolveSpinner");
              sp.classList.remove("d-none"); btn.disabled = true;
              try {
                await Staff.resolve(sid);
                window.showToast("Item resolved. Handover complete.");
                bi.hide();
                if (typeof activeReloadCallback === "function") activeReloadCallback();
              } catch (err) { window.showToast(err.message || "Failed to resolve item", "danger"); }
              sp.classList.add("d-none"); btn.disabled = false;
            };
            bi.show();
          })
          .catch(function (err) {
            window.showToast(err.message || "Failed to fetch item details", "danger");
          });
        return;
      }
    });
  }

  return {
    custodyPill: custodyPill,
    row: row,
    actions: actions,
    claim: claim,
    resolve: resolve,
    custody: custody,
    recipient: recipient,
    bindTableActions: bindTableActions,
  };
})();