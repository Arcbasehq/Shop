# Golders

A small PHP storefront for physical merchandise that accepts **Bitcoin (BTC)** and **Ethereum (ETH)** through **BlockBee** (a non-custodial, no-account, no-KYC payment gateway formerly known as CryptAPI). No JavaScript framework, no CSS framework — vanilla everything.

## Why this stack is private

| Concern | How it's handled |
| --- | --- |
| Identity / KYC | **None.** BlockBee never holds your funds, so they never need to verify who you are. You don't even create an account — you just supply your own wallet addresses and BlockBee generates a unique forwarding address per order. |
| Card data | None collected, never. Payments settle on-chain. |
| Custody | Non-custodial. The first confirmation forwards from BlockBee's intermediate address straight to *your* wallet. |
| Webhook spoofing | Each callback URL embeds a per-order random token compared in constant time, **and** the handler re-calls BlockBee's `/logs` endpoint to authoritatively confirm value + confirmations before flipping the order to `paid`. |
| Password storage | Argon2id (`memory_cost=64 MiB`, `time_cost=4`) via `password_hash`. |
| SQL injection | PDO with `ATTR_EMULATE_PREPARES=false` and prepared statements everywhere. |
| XSS | All output passes through `Security::e()` → `htmlspecialchars(ENT_QUOTES, UTF-8)`. CSP forbids inline scripts and external origins (only the BlockBee QR image is allowlisted). |
| CSRF | Random 32-byte session token enforced on every state-changing route; webhook is exempt because it's authenticated by URL token + server-side re-check. |
| Session fixation | `session_regenerate_id(true)` on login/register; HttpOnly + Secure + SameSite=Strict cookies. |
| Clickjacking | `X-Frame-Options: DENY` and CSP `frame-ancestors 'none'`. |
| MIME sniffing | `X-Content-Type-Options: nosniff`. |
| Transport | HSTS preload header; `.htaccess` includes a (commented) HTTPS-redirect rule for production. |
| Brute force | Login attempts rate-limited per IP **and** per email (5 / 15 min). Failed lookups still verify a dummy hash so timing doesn't leak account existence. |
| Race conditions | Checkout decrements stock inside a `SELECT … FOR UPDATE` transaction. |
| Stale prices | Cart stores only `product_id ⇒ qty`; prices are re-read from the DB at checkout. |
| Underpayment | Order only flips to `paid` if `received_amount >= expected_amount` AND `confirmations >= min_confirmations` (configurable per coin). |

> **Heads up:** "no identity" is true at the gateway. It is not true at the network level — your server's egress IP talks to BlockBee. If that matters, route outbound HTTPS through Tor or a VPN, and serve the storefront behind a Tor hidden service.

## Layout

```
config/         schema.sql, seed.sql, migration_blockbee.sql, config.example.php
public/         DocumentRoot — index.php front controller + CSS/JS/SVG assets
src/            App code (PSR-4-ish under namespace Golders\)
src/Controllers Routes
views/          PHP templates
bin/install.php Schema + seed + admin user creation
storage/logs/   PHP error log (created on first run)
```

## Setup

### 1. Requirements
- PHP **8.1+** with `pdo_mysql`, `curl`, `mbstring`
- MySQL **8.0+** / MariaDB **10.5+**
- A public HTTPS URL (BlockBee needs to reach `/webhook/blockbee` to confirm orders)
- A Bitcoin address (bech32 recommended) and an Ethereum address you control

### 2. Configure
```bash
cp config/config.example.php config/config.php
# edit it: DB creds, app.url, your BTC + ETH wallet addresses, app_key
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"   # paste into app_key
```

### 3. Install
```bash
php bin/install.php
```
You'll be prompted for the admin email and password (Argon2id-hashed, never logged).

If you already ran the older BTCPay-era schema, instead run:
```bash
mysql golders < config/migration_blockbee.sql
```

### 4. Serve
**Apache:** point a vhost's `DocumentRoot` at `public/`. The included `.htaccess` handles the front-controller rewrite and blocks dotfiles.

**nginx:** equivalent —
```nginx
root /path/to/Golders/public;
index index.php;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ {
    fastcgi_pass unix:/run/php/php-fpm.sock;
    include fastcgi.conf;
}
location ~ /\. { deny all; }
```

**Quick local sanity check** (BlockBee callbacks won't reach localhost — use [a tunnel](https://ngrok.com) or a real public host for end-to-end testing):
```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

### 5. BlockBee wiring
No account, no signup. Just make sure the wallet addresses in `config/config.php` under `blockbee.wallets` are addresses **you control** (hardware wallet, Monero CLI, MetaMask, etc.). On checkout:

1. Customer picks BTC or ETH.
2. The app calls BlockBee's `/create/` with your wallet address + a callback URL that contains a per-order random token.
3. BlockBee returns a unique deposit address; the customer sends the exact amount.
4. On the first confirmation, BlockBee forwards funds to your wallet **and** POSTs to your callback URL.
5. The webhook handler verifies the token, re-calls BlockBee's `/logs/` endpoint, and only marks the order paid when confirmations + value check out.

BlockBee's per-tx fee (~1%) is deducted from what arrives in your wallet.

## Routes

- `/` — storefront
- `/cart`, `/checkout` — checkout flow (places order, then redirects to `/order/<id>/pay`)
- `/order/<id>/pay` — coin picker → deposit-address + QR + amount
- `/order/<id>` — order details (also used as BlockBee post-payment redirect)
- `/login`, `/register`, `/account` — customers
- `/admin` — dashboard, `/admin/orders`, `/admin/products` — admin only
- `/webhook/blockbee` — BlockBee callback receiver (URL-token authenticated)

## Production checklist

- [ ] Uncomment the HTTPS redirect block in `public/.htaccess`
- [ ] Set `app.env = production` in `config/config.php`
- [ ] Run behind TLS with HSTS preload enabled (the header is already sent)
- [ ] Restrict MySQL user privileges to this DB only
- [ ] Back up the DB and your wallet seeds (if you lose the wallet seeds, BlockBee can't help — they don't have your coins)
- [ ] Monitor `storage/logs/php-error.log`
- [ ] Confirm `app.url` matches the HTTPS host your server is reachable at (BlockBee needs to call it back)
- [ ] Rotate `app_key` if ever exposed

## License

Use it however you like. No warranty.
