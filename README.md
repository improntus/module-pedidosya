<img src="./view/frontend/web/images/PeYaLogo.svg" align="left"> <p> - Oficial Module - Magento 2</p>
<hr>

## Description
Official module [PedidosYa](https://www.pedidosya.com.ar/) for Magento 2. The module was developed using Pedidos Ya API documentation [API Docs](https://developers.pedidosya.com/courier-api).

### Installation
The module requires Magento 2.0.x or higher for its correct operation. It will need to be installed using the Magento console commands.

```sh
composer require improntus/module-pedidosya-magento-2:^2.0
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

[![N|Solid](https://improntus.com/wp-content/uploads/2022/05/Logo-Site.png)](https://www.improntus.com)

