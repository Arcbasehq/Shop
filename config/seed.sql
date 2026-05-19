-- Seed product data for a physical-merchandise demo store.
-- The admin account is created by bin/install.php (which generates a real
-- Argon2id hash from a password you supply) - not seeded here.
INSERT INTO products (slug, name, description, price_cents, currency, stock, image_path) VALUES
('onyx-tee',       'Onyx Tee',          'Heavyweight 100% cotton tee, screen-printed in small batches.', 3500, 'USD', 50, '/assets/img/onyx-tee.svg'),
('signal-hoodie',  'Signal Hoodie',     'Brushed-fleece pullover, oversized fit, reflective embroidery.', 8900, 'USD', 30, '/assets/img/signal-hoodie.svg'),
('mesh-cap',       'Mesh Cap',          'Six-panel mesh cap with woven label.',                            2800, 'USD', 80, '/assets/img/mesh-cap.svg'),
('canvas-tote',    'Canvas Tote',       '14oz natural canvas tote with reinforced straps.',                 1900, 'USD', 120,'/assets/img/canvas-tote.svg'),
('field-jacket',   'Field Jacket',      'Waxed-canvas field jacket, brass hardware, lined.',               18500, 'USD', 12, '/assets/img/field-jacket.svg'),
('socks-trio',     'Crew Socks (3-pk)', 'Ribbed crew socks, combed-cotton blend, set of three.',            2200, 'USD', 200,'/assets/img/socks-trio.svg');
