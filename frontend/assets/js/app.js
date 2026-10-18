(function () {
var $ = function (i) { return document.getElementById(i) }, S = { me: null, view: "dash", pre: 0, np: false, flash: "", items: [] };
var TAB = { dash: "Dashboard", inv: "Inventory", req: "Supplier Requests", pro: "Inventory Processing", hist: "History", prod: "Products", users: "Users", rep: "Reports" };
var RN = { 1: "Head admin", 2: "Branch admin", 3: "Staff" };
var SUB = { 1: "Monitor and manage any branch.", 2: "Manage stock, supplier requests and deliveries for your branch.", 3: "Check stock, request supplies and record deliveries." };
var RC = { pending: "low", requested: "blue", received: "ok", cancelled: "grey" };
function esc(s) { return String(s == null ? "" : s).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c] }) }
function dt(s) { return s ? new Date(s.replace(" ", "T")).toLocaleString("en-PH", { month: "short", day: "numeric", year: "numeric", hour: "numeric", minute: "2-digit" }) : "" }
function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : "" }
function pill(c, t) { return '<span class="pill ' + c + '">' + esc(t) + '</span>' }
function sp(q) { q = +q; return q <= 0 ? pill("zero", "Out of stock") : q <= 10 ? pill("low", "Low stock ⚠") : pill("ok", "In stock") }
function tbl(h, r) { return '<div class="tw"><table><thead><tr>' + h.map(function (x) { return '<th>' + x + '</th>' }).join("") + '</tr></thead><tbody>' + (r || '<tr><td colspan="' + h.length + '">Nothing to show yet.</td></tr>') + '</tbody></table></div>' }
function li(l, r) { return '<div class="li"><span>' + l + '</span><span class="mu">' + r + '</span></div>' }
function role() { return S.me.user.role }
function P(x) { x = x || {}; if (S.me && role() == 1) x.b = S.me.branch.id; return x }
function opts(a, sel) { return a.map(function (o) { return '<option value="' + o.id + '"' + (o.id == sel ? " selected" : "") + '>' + esc(o.name) + '</option>' }).join("") }
function stats(a) { return '<div class="stats">' + a.map(function (x) { return '<div class="stat"><span>' + x[0] + '</span><b>' + x[1] + '</b></div>' }).join("") + '</div>' }
function combo() { return '<div class="cb"><input class="cbi" placeholder="Search product" autocomplete="off" aria-label="Product"><input type="hidden" name="product_id"><ul class="cbl" hidden></ul></div>' }
function mv(r) { return r.map(function (x) { return '<tr><td>' + dt(x.created_at) + '</td><td><b>' + esc(x.product) + '</b></td><td>' + cap(x.type.replace("_", " ")) + '</td><td>' + (x.qty > 0 ? "+" : "") + x.qty + " " + esc(x.unit) + '</td><td>' + esc(x.reason) + '</td><td>' + esc(x.by_name) + '</td></tr>' }).join("") }
var MVH = ["Date and time", "Product", "Type", "Quantity", "Details", "Processed by"];

// ----- screen shell (header, banner, tabs, notifications) -----
function tabs() { var t = ["dash", "inv", "req", "pro", "hist"]; if (role() == 1) t.push("prod"); if (role() < 3) t.push("users", "rep"); return t }
function shell() {
  var m = S.me, u = m.user, l = m.low;
  $("hdr").innerHTML = '<header><div class="logo"><i class="leaf"></i>Chingkeetea IMS</div><div class="sp"></div>'
    + (u.role == 1 ? '<select id="bsw" aria-label="Branch">' + opts(m.branches, m.branch.id) + '</select>' : '<span>' + esc(m.branch.name) + ' branch</span>')
    + '<div class="bw"><button class="out bell" id="bell" type="button" aria-label="Notifications">🔔' + (l.length ? '<b class="bd">' + l.length + '</b>' : "") + '</button><div class="np" id="np"' + (S.np ? "" : " hidden") + '><div class="nh">Notifications</div>'
    + (l.map(function (r) { return '<div class="nt"><b>🔔 Low Stock Alert</b><p><strong>' + esc(r.name) + '</strong> is ' + (r.quantity <= 0 ? "out of stock" : "low in stock") + '.<br>Current quantity: ' + r.quantity + " " + esc(r.unit) + '</p><p class="mu">Consider requesting additional stock.</p><button class="sm" data-rq="' + r.id + '">Request stock</button></div>' }).join("") || '<div class="nt mu">No low-stock alerts.</div>')
    + '</div></div><span class="role">' + RN[u.role] + '</span><span>' + esc(u.name) + '</span><button class="out" id="lo">Sign out</button></header>'
    + '<div class="banner"><div class="wrap" style="padding:0"><h1>' + esc(m.branch.name) + ' branch</h1><p>' + SUB[u.role] + '</p></div></div>'
    + '<nav class="tabs">' + tabs().map(function (t) { return '<button class="tab' + (t === S.view ? " on" : "") + '" data-tab="' + t + '">' + TAB[t] + '</button>' }).join("") + '</nav>';
}
function load() { return api("me", P()).then(function (d) { S.me = d; shell(); return show() }) }
function show() {
  $("body").innerHTML = '<p class="mu">Loading…</p>';
  return V[S.view]().then(function (h) {
    $("body").innerHTML = (S.flash ? '<div class="msg g" role="status">' + esc(S.flash) + '</div>' : "") + h; S.flash = ""; wire();
  }).catch(fail);
}
function fail(e) { if (e.status === 401) return logoutUi(); $("body").innerHTML = '<div class="msg">' + esc(e.message) + '</div>' }
function logoutUi() { $("app").hidden = true; $("login").hidden = false; $("p").value = ""; $("m").textContent = ""; S.me = null }

// ----- views -----
var V = {
 dash: function () { return api("dashboard", P()).then(function (d) { var s = d.stats;
  return stats([["Total products", s.total], ["Available stock", s.available + " in stock"], ["Low stock items", s.low], ["Pending stock requests", s.pending]])
   + '<div class="grid2"><div class="panel"><h2>Recently received products</h2>' + (d.received.map(function (x) { return li('<b>' + esc(x.product) + '</b> +' + x.qty + " " + esc(x.unit), dt(x.created_at)) }).join("") || '<p class="mu">Nothing received yet.</p>') + '</div>'
   + '<div class="panel"><h2>Pending stock requests</h2>' + (d.pending.map(function (x) { return li('<b>' + esc(x.product) + '</b> ' + x.qty + " " + esc(x.unit) + " " + pill(RC[x.status], cap(x.status)), dt(x.created_at)) }).join("") || '<p class="mu">No pending requests.</p>') + '</div></div>'
   + '<div class="panel"><h2>Recent stock transactions</h2>' + tbl(MVH, mv(d.transactions)) + '</div>' }) },
 inv: function () { return api("inventory", P()).then(function (d) { S.rows = d.rows;
  return '<div class="panel"><div class="tools"><input id="q" placeholder="Search items" aria-label="Search items"><select id="st" aria-label="Status"><option value="">All status</option><option value="low">Low stock</option><option value="out">Out of stock</option><option value="ok">In stock</option></select></div><div class="tw"><table><thead><tr><th>Product</th><th>Category</th><th>Stock</th><th>Status</th><th></th></tr></thead><tbody id="tb"></tbody></table></div></div>' }) },
 req: function () { return Promise.all([api("inventory", P()), api("requests", P())]).then(function (x) { S.items = x[0].rows; var u = S.me.user;
  var h = x[1].rows.map(function (r) { var open = r.status === "pending" || r.status === "requested", a = "";
   if (open && (u.role < 3 || r.requested_by == u.id)) a += '<button class="sm" data-ca="' + r.id + '">Cancel</button> ';
   if (r.status === "pending" && u.role < 3) a += '<button class="sm" data-ma="' + r.id + '">Mark requested</button>';
   return '<tr><td>' + dt(r.created_at) + '</td><td><b>' + esc(r.product) + '</b></td><td>' + r.qty + " " + esc(r.unit) + '</td><td class="msgc">' + esc(r.message) + '</td><td>' + esc(r.by_name) + '</td><td>' + pill(RC[r.status], cap(r.status)) + (r.received_at ? '<br><small class="mu">' + dt(r.received_at) + '</small>' : "") + '</td><td>' + a + '</td></tr>' }).join("");
  return '<div class="panel pf"><h2>Request to supplier</h2><p class="sub">Send a stock request when supplies are running low.</p><form data-f="send"><label>Product</label>' + combo() + '<div class="note" id="cn"></div><label for="qty">Quantity</label><input id="qty" name="qty" type="number" min="1" placeholder="10"><label for="rm">Message or remarks</label><input id="rm" name="message" maxlength="255" placeholder="Requesting 10 packs of pearls for branch inventory."><button class="go" type="submit">Send request</button><div class="msg" id="em" role="alert"></div></form></div>'
   + '<div class="panel"><h2>Sent requests and status</h2>' + tbl(["Date and time", "Product", "Quantity", "Message", "Requested by", "Status", ""], h) + '</div>' }) },
 pro: function () { return Promise.all([api("inventory", P()), api("processing", P())]).then(function (x) { S.items = x[0].rows;
  return '<div class="panel pf"><h2>Inventory Processing</h2><p class="sub">Record products delivered by the supplier.</p><form data-f="pro"><label>Product</label>' + combo() + '<label for="qty">Quantity</label><input id="qty" name="qty" type="number" min="1" placeholder="20"><div class="prev" id="pv"></div><button class="go" type="submit">Add Received Stock</button><div class="msg" id="em" role="alert"></div></form></div>'
   + '<div class="panel"><h2>Recently processed</h2>' + tbl(MVH, mv(x[1].rows)) + '</div>' }) },
 hist: function () { return api("history", P()).then(function (d) { return '<div class="panel"><h2>Stock transaction history</h2>' + tbl(MVH, mv(d.rows)) + '</div><p class="mu">Showing the latest 300 transactions.</p>' }) },
 users: function () { return api("users", P()).then(function (d) { var head = role() == 1;
  var h = d.rows.map(function (r) { return '<tr><td><b>' + esc(r.name) + '</b></td><td>' + esc(r.email) + '</td><td>' + RN[r.role] + '</td><td>' + esc(r.branch || "All branches") + '</td><td>' + pill(r.status === "active" ? "ok" : "grey", cap(r.status)) + '</td><td>' + (r.role > 1 && (head || r.role == 3) ? '<button class="sm" data-ut="' + r.id + '">' + (r.status === "active" ? "Deactivate" : "Activate") + '</button>' : "") + '</td></tr>' }).join("");
  return '<div class="panel"><h2>Add ' + (head ? "user" : "staff") + '</h2><form data-f="user" class="fr"><div><label>Full name</label><input name="name"></div><div><label>Email</label><input name="email" placeholder="name@branch.staff.com"></div><div><label>Password</label><input name="password" type="password"></div>'
   + (head ? '<div><label>Role</label><select name="role"><option value="3">Staff</option><option value="2">Branch admin</option></select></div><div><label>Branch</label><select name="branch">' + opts(d.branches, S.me.branch.id) + '</select></div>' : "")
   + '<div><button class="go" type="submit">Create account</button></div></form><div class="msg" id="em" role="alert"></div></div><div class="panel"><h2>' + (head ? "All users" : "Branch team") + '</h2>' + tbl(["Name", "Email", "Role", "Branch", "Status", ""], h) + '</div>' }) },
 prod: function () { return api("products").then(function (d) {
  var h = d.rows.map(function (r) { return '<tr><td>' + esc(r.sku) + '</td><td><b>' + esc(r.name) + '</b></td><td>' + esc(r.category) + '</td><td>' + esc(r.unit) + '</td><td>' + pill(r.status === "active" ? "ok" : "grey", cap(r.status)) + '</td><td><button class="sm" data-pt="' + r.id + '">' + (r.status === "active" ? "Deactivate" : "Activate") + '</button></td></tr>' }).join("");
  return '<div class="panel"><h2>Add product</h2><form data-f="prod" class="fr"><div><label>SKU</label><input name="sku" placeholder="TOP-010"></div><div><label>Product name</label><input name="name"></div><div><label>Category</label><select name="category">' + opts(d.categories) + '</select></div><div><label>Unit</label><input name="unit" placeholder="kg, pcs, tub"></div><div><label>Usual full stock</label><input name="par" type="number" min="0" value="20"></div><div><button class="go" type="submit">Add product</button></div></form><div class="msg" id="em" role="alert"></div></div><div class="panel"><h2>All products</h2>' + tbl(["SKU", "Product", "Category", "Unit", "Status", ""], h) + '</div>' }) },
 rep: function () { var d = new Date(), f = new Date(d - 30 * 864e5), iso = function (x) { return x.toISOString().slice(0, 10) };
  return Promise.resolve('<div class="panel"><h2>Generate report</h2><form data-f="rep" class="fr"><div><label>Report</label><select name="type"><option value="inventory">Inventory report</option><option value="stock_in">Stock-in report</option><option value="stock_out">Stock-out report</option></select></div>'
   + (role() == 1 ? '<div><label>Branch</label><select name="rb"><option value="0">All branches</option>' + opts(S.me.branches) + '</select></div>' : "")
   + '<div><label>From</label><input type="date" name="from" value="' + iso(f) + '"></div><div><label>To</label><input type="date" name="to" value="' + iso(d) + '"></div><div><button class="go" type="submit">View report</button></div></form><div class="msg" id="em" role="alert"></div></div><div id="rr"></div>') }
};

// ----- searchable product dropdown, inventory filter, previews -----
function rowsInv() {
  var q = ($("q").value || "").toLowerCase(), st = $("st").value, h = "";
  S.rows.forEach(function (r) { if (q && r.name.toLowerCase().indexOf(q) < 0) return; if (st && r.status !== st) return;
   h += '<tr><td><b>' + esc(r.name) + '</b></td><td>' + esc(r.category) + '</td><td>' + r.quantity + " " + esc(r.unit) + '</td><td>' + sp(r.quantity) + '</td><td>' + (r.quantity <= 10 ? '<button class="sm" data-rq="' + r.id + '">Request stock</button>' : "") + '</td></tr>' });
  $("tb").innerHTML = h || '<tr><td colspan="5">No items match.</td></tr>';
}
function wire() {
  if ($("tb")) rowsInv();
  var cb = document.querySelector("#body .cb"); if (!cb) return;
  var inp = cb.querySelector(".cbi"), hid = cb.querySelector("input[type=hidden]"), ul = cb.querySelector("ul"), qty = $("qty"), pv = $("pv"), cn = $("cn"), cur = null;
  function preview() { if (!cur) return; var n = parseInt(qty.value, 10) || 0, u = esc(cur.unit);
   if (pv) pv.innerHTML = '<div>Current stock<b>' + cur.quantity + '</b>' + u + '</div><div>Received<b>+' + n + '</b>' + u + '</div><div>New stock<b>' + (cur.quantity + n) + '</b>' + u + '</div>';
   if (cn) cn.textContent = "Current stock: " + cur.quantity + " " + cur.unit + (cur.quantity <= 10 ? " · needs replenishment" : "") }
  function pick(it) { hid.value = it.id; inp.value = it.name; ul.hidden = true; cur = it; preview() }
  function list() { var q = inp.value.toLowerCase(), h = ""; S.items.forEach(function (i) { if ((i.name + " " + i.category).toLowerCase().indexOf(q) < 0) return; h += '<li data-id="' + i.id + '"><span>' + esc(i.name) + '</span><small>' + i.quantity + " " + esc(i.unit) + '</small></li>' }); ul.innerHTML = h || '<li class="mu">No product found</li>'; ul.hidden = false }
  inp.addEventListener("focus", list); inp.addEventListener("input", function () { hid.value = ""; cur = null; list() });
  inp.addEventListener("keydown", function (e) { if (e.key === "Enter") { e.preventDefault(); var f = ul.querySelector("li[data-id]"); if (f) f.click() } });
  ul.addEventListener("click", function (e) { var l = e.target.closest("li[data-id]"); if (l) S.items.forEach(function (i) { if (i.id == l.dataset.id) pick(i) }) });
  if (qty) qty.addEventListener("input", preview);
  if (S.pre) { S.items.forEach(function (i) { if (i.id == S.pre) pick(i) }); S.pre = 0 }
}

// ----- actions -----
function say(t) { var m = $("em"); if (m) m.textContent = t; else alert(t) }
function done(r) { S.flash = r.message; return load() }
function act(p) { return p.then(done).catch(function (e) { if (e.status === 401) logoutUi(); else say(e.message) }) }
var F = {
 send: function (d) { if (!d.product_id) return say("Select a product from the list."); return act(api("requests", P(), { a: "send", product_id: +d.product_id, qty: +d.qty, message: d.message })) },
 pro: function (d) { if (!d.product_id) return say("Select a product from the list."); return act(api("processing", P(), { product_id: +d.product_id, qty: +d.qty })) },
 user: function (d) { d.a = "create"; return act(api("users", P(), d)) },
 prod: function (d) { d.a = "create"; return act(api("products", P(), d)) },
 rep: function (d) {
  var p = P({ type: d.type, from: d.from, to: d.to }); if (d.rb) p.rb = d.rb;
  return api("reports", p).then(function (r) { $("em").textContent = "";
   $("rr").innerHTML = '<div class="panel"><h2>' + cap(d.type.replace("_", "-")) + ' report</h2><p><a class="sm" href="' + API + "reports.php?" + new URLSearchParams(Object.assign({ csv: 1 }, p)) + '">Download CSV</a></p>'
    + tbl(r.columns.length ? r.columns.map(esc) : ["Result"], r.rows.map(function (x) { return "<tr>" + x.map(function (v) { return "<td>" + esc(v) + "</td>" }).join("") + "</tr>" }).join("")) + '</div>' }).catch(function (e) { say(e.message) }) }
};
$("body").addEventListener("submit", function (e) { e.preventDefault(); var f = e.target.dataset.f; if (F[f]) F[f](Object.fromEntries(new FormData(e.target))) });
$("body").addEventListener("input", function (e) { if (e.target.id === "q" || e.target.id === "st") rowsInv() });
document.addEventListener("click", function (e) { if (!e.target.closest(".cb"))document.querySelectorAll(".cbl").forEach(function (u) { u.hidden = true }); if (S.np && !e.target.closest(".bw")) { S.np = false; if ($("np")) $("np").hidden = true } });
$("app").addEventListener("click", function (e) {
  var b = e.target.closest("button"); if (!b) return; var d = b.dataset;
  if (b.id === "bell") { S.np = !S.np; $("np").hidden = !S.np }
  else if (b.id === "lo") api("logout", null, {}).then(logoutUi, logoutUi)
  else if (d.tab) { S.view = d.tab; shell(); show() }
  else if (d.rq) { S.pre = +d.rq; S.view = "req"; S.np = false; load() }
  else if (d.ca) act(api("requests", P(), { a: "cancel", id: +d.ca }))
  else if (d.ma) act(api("requests", P(), { a: "mark", id: +d.ma }))
  else if (d.ut) act(api("users", P(), { a: "toggle", id: +d.ut }))
  else if (d.pt) act(api("products", P(), { a: "toggle", id: +d.pt }))
});
$("app").addEventListener("change", function (e) { if (e.target.id === "bsw") { S.np = false; S.me.branch.id = +e.target.value; load() } });

// ----- login -----
function start() { $("login").hidden = true; $("app").hidden = false; S.view = "dash"; return load() }
$("f").addEventListener("submit", function (e) {
  e.preventDefault(); var m = $("m"); m.textContent = "";
  if (!$("u").value.trim() || !$("p").value) { m.textContent = "Enter your email and password."; return }
  api("login", null, { email: $("u").value, password: $("p").value }).then(start).catch(function (e) { m.textContent = e.message });
});
api("me").then(start, function () {}); // already signed in? go straight to the dashboard
})();
