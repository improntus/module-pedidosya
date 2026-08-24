CHANGELOG
---------

### 2.0.3
- Compatibility with Adobe Commerce 2.4.9 / Magento 2 Open Source 2.4.9
- Fixed a regression introduced in 2.0.2: while converting the implicitly nullable constructor parameters to explicit `?Type`, the `= null` defaults were dropped. That turned optional arguments into required ones, so the object manager tried to instantiate the abstract `AbstractResource` and the `AdapterInterface` interface. `PedidosYaFactory`, `WaypointFactory` and `TokenFactory` all fataled with "Cannot instantiate abstract class", breaking the admin shipment and waypoint controllers and the sales order view plugin. Restored the defaults in `Model\PedidosYa`, `Model\Token`, `Model\Waypoint` and their three resource collections, matching the core signatures

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
