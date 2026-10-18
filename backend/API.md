# Chingkeetea backend API (for the front end)

Base URL from the front end: `../backend/api/` · Every response is JSON · Errors look like `{ "error": "message" }`
with status 401 (not signed in), 403 (role not allowed) or 400/405 (bad request).
Every POST needs the header `X-Requested-With: fetch` and a JSON body. `frontend/assets/js/api.js` does this for you:
`api("requests", {b: 2}, {a: "send", product_id: 5, qty: 10})`. Head admins add `?b=<branch id>` to choose a branch.

| Endpoint | Method | Roles | Body / query | Returns |
|---|---|---|---|---|
| `login.php` | POST | all | `{email, password}` | `{ok:true}` |
| `logout.php` | POST | all | `{}` | `{ok:true}` |
| `me.php` | GET | all | `?b=` (head admin) | `{user, branch, branches, low}` (`low` = notifications) |
| `dashboard.php` | GET | all | | `{stats, received, transactions, pending}` |
| `inventory.php` | GET | all | | `{rows:[{id,name,category,unit,quantity,status}]}` |
| `requests.php` | GET | all | | `{rows:[...]}` |
| `requests.php` | POST | all | `{a:"send", product_id, qty, message}` | `{message}` |
| `requests.php` | POST | all / admins | `{a:"cancel"\|"mark", id}` (`mark` = admins only) | `{message}` |
| `processing.php` | GET | all | | `{rows}` recently received |
| `processing.php` | POST | all | `{product_id, qty}` | `{message}` (adds stock, saves history) |
| `history.php` | GET | all | | `{rows}` latest 300 movements |
| `users.php` | GET / POST | admins | POST `{a:"create", name,email,password,role,branch}` or `{a:"toggle", id}` | `{rows, branches}` / `{message}` |
| `products.php` | GET / POST | head admin | POST `{a:"create", sku,name,category,unit,par}` or `{a:"toggle", id}` | `{rows, categories}` / `{message}` |
| `reports.php` | GET | admins | `?type=inventory\|stock_in\|stock_out&from=&to=&rb=&csv=1` | `{columns, rows}` or a CSV file |

Roles: 1 head admin, 2 branch admin, 3 staff. Low stock = quantity 10 or below.
Stock is never edited directly: inserting into `stock_movements` fires database triggers that update `inventory` and the low-stock alerts.
