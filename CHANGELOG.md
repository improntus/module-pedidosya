CHANGELOG
---------

### 2.1.0
- Fixed: a fatal error that broke customer order view/print pages store-wide when the module was installed (block registry).
- Security: encrypt all carrier credentials at rest and stop sending them in the request URL; added a one-time migration that encrypts previously stored plaintext credentials across all scopes.
- Security: the admin Request Driver / Cancel Driver / waypoint actions now require a POST request with form key (CSRF protection).
- Security: encrypt the carrier access token at rest, escape proof-of-delivery / tracking output, and restrict tracking links to the carrier domain over HTTPS.
- Security: redact customer data and credentials from logs; debug payload logging is off by default.
- Reliability: prevent a repeated shipment-request loop on carrier errors (which could request a rider multiple times); a failed dispatch is now attempted once and can be retried manually from the admin.
- Reliability: no longer offer a 0.00 rate when the carrier returns no valid fee, and no longer fail checkout rate collection when no waypoint is available.
- Fixed: the recipient name sent to the carrier, the delivery time sent in UTC, working-hours "unavailable" handling, and multi-store credential resolution.
- Fixed: send the shipment confirmation body as JSON; harden request handling and input validation on the waypoint form.
- Packaging: declared MIT license (added `LICENSE.txt`), declared module dependencies, and removed the deprecated `setup_version`.

### 2.0.2
- Compatibility with Adobe Commerce 2.4.8-p1 / Magento 2 Open Source 2.4.8-p1

### 2.0.1
- Compatibility with Adobe Commerce 2.4.7 / Magento 2 Open Source 2.4.7

### 2.0.0
- PedidosYa API [v3](https://developers.pedidosya.com/courier-api/v3) Implementation

### 1.0.29
- Improvements multi-store Support

### 1.0.28
- Add Multi-store Support
- Update Module ACL
- Update Configurations
- Update Webservice

### 1.0.27
- Details in System.xml

### 1.0.25
- Compatibility with Magento 2.4.5
