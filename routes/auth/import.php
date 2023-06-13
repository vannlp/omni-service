<?php

$api->post('/import/product-categories', [
    'action' => 'IMPORT-CATEGORY',
    'uses'   => 'Import\ProductCategoryImportController@init',
]);

$api->post('/import/customers', [
    //    'action' => 'IMPORT-CUSTOMER',
    'uses' => 'Import\UserImportController@init',
]);

$api->post('/import/customers2', [
    //    'action' => 'IMPORT-CUSTOMER',
    'uses' => 'Import\Customer2ImportController@import',
]);
$api->post('/import/customers3', [
//    'action' => 'IMPORT-CUSTOMER',
'uses' => 'Import\Customer3ImportController@import',
]);
$api->post('/import/customer-attributes', [
//    'action' => 'IMPORT-CUSTOMER',
'uses' => 'Import\CustomerAttributeImportController@import',
]);
$api->post('/import/customer-attribute-details', [
//    'action' => 'IMPORT-CUSTOMER',
'uses' => 'Import\CustomerAttributeDetailImportController@import',
]);

$api->post('/import/products', [
    //    'action' => 'IMPORT-PRODUCT',
    'uses' => 'Import\ProductImportController@import',
]);

$api->post('/import/VNS-products', [
    //    'action' => 'IMPORT-PRODUCT',
        'uses' => 'Import\ProductVNSImportController@import',
    ]);

$api->post('/import/VNS-product-info', [
    //    'action' => 'IMPORT-PRODUCT',
        'uses' => 'Import\ProductInfoVNSImportController@import',
    ]);

$api->post('/import/areas', [
    //    'action' => 'IMPORT-PRODUCT',
    'uses' => 'Import\AreaImportController@import',
]);
$api->post('/import/staff-sync', [
//    'action' => 'IMPORT-PRODUCT',
    'uses' => 'Import\StaffSyncImportController@import',
]);

$api->post('/import/shop-sync', [
    //    'action' => 'IMPORT-PRODUCT',
        'uses' => 'Import\ShopSyncImportController@import',
    ]);

$api->post('/import/warehouses', [
    //    'action' => 'IMPORT-PRODUCT',
    'uses' => 'Import\WarehouseImportController@import',
]);
$api->post('/import/units', [
    'action' => '',
    'uses'   => 'Import\UnitImportController@import',
]);

$api->post('/import/specifications', [
    'action' => '',
    'uses'   => 'Import\SpecificationImportController@import',
]);


$api->post('/import/import-product', [
    //    'action' => 'IMPORT-PRODUCT',
    'uses' => 'Import\ImportProductController@import',
]);

$api->post('/import/import-update-product', [
    //    'action' => 'IMPORT-PRODUCT',
    'uses' => 'Import\ImportUpdateProductController@import',
]);

$api->post('/import/import-product-category', [
    //    'action' => 'IMPORT-CATEGORY',
    'uses' => 'Import\ImportProductCategoryController@import',
]);

$api->post('/import/brands', [
    'action' => '',
    'uses'   => 'Import\BrandImportController@import',
]);

$api->post('/import/batches', [
    'action' => '',
    'uses'   => 'Import\BatchImportController@import',
]);

$api->post('/import/user-groups', [
    'action' => '',
    'uses'   => 'Import\UserGroupImportController@import',
]);
$api->post('/import/prices', [
    'action' => '',
    'uses'   => 'Import\PriceImportController@import',
]);
$api->post('/import/coupon', [
    'action' => '',
    'uses'   => 'Import\CouponImportController@import',
]);
$api->post('/import/saleOrderConfigMin', [
    'action' => '',
    'uses'   => 'Import\SaleOrderConfigMinImportController@import',
]);

$api->post('/import/price-customer-deduceds', [
    'action' => '',
    'uses'   => 'Import\PriceCustomerDeducedImportController@import',
]);

$api->post('/import/price-infos', [
    'action' => '',
    'uses'   => 'Import\PriceInfoImportController@import',
]);

$api->post('/import/warehouse2', [
        'uses' => 'Import\Warehouse2ImportController@import',

]);

$api->post('/import/warehouse-detail', [
    'uses' => 'Import\WarehouseDetailImportController@import',

]);

$api->post('/import/channel', [
    'uses' => 'Import\ChannelImportController@import',
]);

$api->post('/import/routing', [
    'action'    => '',
    'uses'      => 'Import\RoutingImportController@import'
]);

$api->post('/import/routing-customer', [
    'action'    => '',
    'uses'      => 'Import\RoutingCustomerImportController@import'
]);

$api->post('/import/visit-plan', [
    'action'    => '',
    'uses'      => 'Import\VisitPlanImportController@import'

]);
$api->post('/import/virtual-account', [
    'action'    => '',
    'uses'      => 'Import\VirtualAccountImportController@import'
]);

$api->post('/import/inter-district', [
    'action'    => '',
    'uses'      => 'Import\InterDistrictImportController@import'
]);

$api->post('/import/categories', [
    'action' => '',
    'uses'   => 'Import\DistributorImportController@import',
]);

$api->post('/import/product-hubs', [
    'action' => '',
    'uses'   => 'Import\ProductHubImportController@import',
]);
$api->post('/import/users', [
    'action' => '',
    'uses'   => 'Import\CustomerImportController@import',
]);

$api->post('/import/users-customers', [
    'action' => '',
    'uses'   => 'Import\Customer2ImportController@import',
]);$api->post('/import/distributor-information', [
    'action' => '',
    'uses'   => 'Import\DistributorInformationImportController@import',
]);

$api->post('/import/sale-area', [
    'action' => '',
    'uses'   => 'Import\ProductSaleAreaImportController@import',
]);

$api->post('/import/product-in-category/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'Import\ProductInCategoryImportController@import',
]);

$api->post('/import/coupon-code/{id:[0-9]+}', [
    'action' => '',
    'uses' => 'Import\CouponCodeImportController@import',
]);
$api->post('/import/sale-prices', [
    'action' => '',
    'uses'   => 'Import\PriceImportController@import',
]);