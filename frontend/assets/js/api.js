// All calls to the backend go through this file. See backend/API.md for the endpoints.
var API = "../backend/api/";
function api(name, params, body) {
  var url = API + name + ".php" + (params && Object.keys(params).length ? "?" + new URLSearchParams(params) : "");
  var o = { credentials: "same-origin", headers: { "X-Requested-With": "fetch" } };
  if (body) { o.method = "POST"; o.headers["Content-Type"] = "application/json"; o.body = JSON.stringify(body); }
  return fetch(url, o).then(function (r) {
    return r.json().catch(function () { return { error: "Server error. Is MySQL running and is config.php correct?" }; }).then(function (d) {
      if (!r.ok) { var e = new Error(d.error || "Request failed"); e.status = r.status; throw e; }
      return d;
    });
  });
}
