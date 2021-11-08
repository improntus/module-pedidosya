# Pedidos Ya module - Magento 2

## Description
Official module [Pedidos Ya](https://www.pedidosya.com.ar/) for Magento 2. The module was developed using Pedidos Ya API documentation [API Docs](https://developers.pedidosya.com/courier-api).

### Installation
The module requires Magento 2.0.x or higher for its correct operation. It will need to be installed using the Magento console commands.

```sh
composer require improntus/module-pedidosya-magento-2
```

Developer installation mode

```sh
php bin/magento deploy:mode:set developer
php bin/magento module:enable Improntus_PedidosYa
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy es_AR en_US
php bin/magento setup:di:compile
```

Production installation mode

```sh
php bin/magento module:enable Improntus_PedidosYa
php bin/magento setup:upgrade
php bin/magento deploy:mode:set production
```
 
## Author

[![N|Solid](https://www.improntus.com/developed-by-small.png)](https://www.improntus.com)

