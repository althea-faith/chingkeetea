CHINGKEETEA INVENTORY MANAGEMENT SYSTEM 


SAMPLE ACCOUNTS (password for all: Chingkee@2026)
  Head admin    ralph@head.admin.com
  Branch admin  carvero.althea@velez.admin.com
  Staff         cruz.paolo@velez.staff.com


WHO CAN DO WHAT (enforced on the server)
  Staff         Dashboard, Inventory, Supplier Requests (create, cancel own), Inventory Processing, History - own branch only
  Branch admin  Everything staff can, plus mark requests as Requested, cancel any request, Users (staff of own branch), Reports - own branch only
  Head admin    Everything, for any branch (branch selector at the top), plus Products and Users for all branches

WORKFLOW
  Staff request -> Pending -> admin marks Requested (sent to supplier) -> supplier delivers
  -> Inventory Processing (search product, quantity, Add Received Stock) -> stock updated, history saved,
  matching request becomes Received -> stock at 10 or below shows a Low Stock notification (bell) -> request again.

Edited by Mochiroot
