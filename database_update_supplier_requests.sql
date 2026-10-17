
USE chingkeetea_ims;

ALTER TABLE stock_requests
  MODIFY status ENUM('pending','approved','rejected','cancelled','fulfilled','requested','received') NOT NULL DEFAULT 'pending';
UPDATE stock_requests SET status='requested' WHERE status='approved';
UPDATE stock_requests SET status='received'  WHERE status='fulfilled';
UPDATE stock_requests SET status='cancelled' WHERE status='rejected';
ALTER TABLE stock_requests
  MODIFY status ENUM('pending','requested','received','cancelled') NOT NULL DEFAULT 'pending',
  ADD COLUMN received_at DATETIME NULL AFTER reviewed_at;

-- Low-stock threshold: 10 or below for every product.
UPDATE products SET reorder_level = 10;
UPDATE low_stock_alerts a JOIN inventory i ON i.branch_id = a.branch_id AND i.product_id = a.product_id
  SET a.status = 'resolved', a.resolved_at = NOW() WHERE a.status = 'open' AND i.quantity > 10;
INSERT INTO low_stock_alerts (branch_id, product_id, quantity_at_alert)
  SELECT i.branch_id, i.product_id, i.quantity FROM inventory i
  WHERE i.quantity <= 10 AND NOT EXISTS (SELECT 1 FROM low_stock_alerts a WHERE a.branch_id = i.branch_id AND a.product_id = i.product_id AND a.status = 'open');
