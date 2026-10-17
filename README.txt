CHINGKEETEA INVENTORY MANAGEMENT SYSTEM - PHP + MySQL (XAMPP / phpMyAdmin)

SETUP
1. Copy this "chingkeetea" folder into C:\xampp\htdocs\
2. Open the XAMPP Control Panel and start Apache and MySQL.
3. Open http://localhost/phpmyadmin
   a. Click Import > choose chingkeetea_ims.sql > Go   (creates the chingkeetea_ims database)
   b. Click the chingkeetea_ims database > SQL tab > paste/run database_update_supplier_requests.sql
4. Check config.php (XAMPP default: user root, empty password).
5. Open http://localhost/chingkeetea/

SAMPLE ACCOUNTS (password for all: Chingkee@2026)
  Head admin    ralph@head.admin.com
  Branch admin  carvero.althea@velez.admin.com
  Staff         cruz.paolo@velez.staff.com
Change these passwords before real use. New users get password_hash() passwords.

WHO CAN DO WHAT (enforced on the server)
  Staff         Dashboard, Inventory, Supplier Requests (create, cancel own), Inventory Processing, History - own branch only
  Branch admin  Everything staff can, plus mark requests as Requested, cancel any request, Users (staff of own branch), Reports - own branch only
  Head admin    Everything, for any branch (branch selector at the top), plus Products and Users for all branches

WORKFLOW
  Staff request -> Pending -> admin marks Requested (sent to supplier) -> supplier delivers
  -> Inventory Processing (search product, quantity, Add Received Stock) -> stock updated, history saved,
  matching request becomes Received -> stock at 10 or below shows a Low Stock notification (bell) -> request again.

FILES
  index.php login | dashboard.php | inventory.php | requests.php | processing.php | history.php
  users.php | products.php (head admin) | reports.php (inventory, stock-in, stock-out + CSV) | logout.php
  inc/auth.php database, sessions, permissions | inc/layout.php page layout and notification bell
  assets/ css, js, images
Stock changes are made by inserting into stock_movements; database triggers update inventory and low-stock alerts.

USING VISUAL STUDIO CODE
1. Install VS Code (code.visualstudio.com) and open this folder: File > Open Folder > chingkeetea.
2. Install the recommended extensions when VS Code asks (PHP Intelephense, PHP Debug, MySQL client).
3. Start MySQL in the XAMPP Control Panel (Apache is not needed for step 4).
4. Press Ctrl+Shift+B to run the PHP server, then open http://localhost:8000/
   (the task uses C:\xampp\php\php.exe; change the path in .vscode/tasks.json if PHP is elsewhere).
Or keep the folder in C:\xampp\htdocs\chingkeetea and open http://localhost/chingkeetea/ with Apache running.
