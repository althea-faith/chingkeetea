# Chingkeetea Inventory Management System

```
chingkeetea/
├── frontend/   HTML, CSS, JavaScript, images          (front-end developer)
├── backend/    PHP JSON API, config, DB connection    (back-end developer)
├── database/   chingkeetea_ims.sql + update script    (import in phpMyAdmin)
└── index.html  opens the front end
```
The front end never touches the database. It calls the backend only through `backend/api/*.php` (see `backend/API.md`).

## Run it (XAMPP + VS Code)
1. Start **MySQL** in XAMPP. In phpMyAdmin: **Import** `database/chingkeetea_ims.sql`, then run `database/database_update_supplier_requests.sql` in the **SQL** tab.
2. Check `backend/config.php` (host, **port** 3307 or 3306, user, password).
3. VS Code: open this folder, press **Ctrl+Shift+B** (Run Chingkeetea), then open **http://localhost:8000/**.
   Or put the folder in `C:\xampp\htdocs\` and open `http://localhost/chingkeetea/` with Apache running.
Open the front end through the PHP server, not Live Server or a double-click, so the login cookie works.

## Sample accounts (password `Chingkee@2026`)
Head admin `ralph@head.admin.com` · Branch admin `carvero.althea@velez.admin.com` · Staff `cruz.paolo@velez.staff.com`

## Working together on GitHub
Front end changes only in `frontend/`, back end only in `backend/` and `database/`, so merges rarely conflict.
Before you start: **Sync Changes**. When you finish: **Commit**, then **Sync Changes**.
