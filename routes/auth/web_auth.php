<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// Authorized Group
$api->version('v1', [
    'middleware' => [
        'cors2',
        'trimInput',
        'verifySecret',
        'authorize',
    ],
], function ($api) {
    $api->group(['prefix' => 'v1', 'namespace' => 'App\V1\Controllers'], function ($api) {

        $api->options('/{any:.*}', function () {
            return response(['status' => 'success'])
                ->header('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, DELETE')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Origin');
        });

        $api->get('/', function () {
            return ['api-status' => 'Ok. Ready!'];
        });

        // Auth
        require __DIR__ . '/auth.php';

        // Users
        require __DIR__ . '/user.php';

        // Role
        require __DIR__ . '/role.php';

        // Permission
        require __DIR__ . '/permission.php';

        // Permission Group
        require __DIR__ . '/permission_group.php';

        // Product
        require __DIR__ . '/products.php';

        require __DIR__ . '/shop.php';

        // Category
        require __DIR__ . '/category.php';

        // Master Data
        require __DIR__ . '/master_data.php';

        // Master Data Type
        require __DIR__ . '/masterdata_type.php';

        // Master Data Type
        require __DIR__ . '/card.php';

        // File
        require __DIR__ . '/file.php';

        // Folder
        require __DIR__ . '/folder.php';

        // Order
        require __DIR__ . '/order.php';

        // Wallet
        require __DIR__ . '/wallet.php';

        // User Location
        require __DIR__ . '/user_location.php';

        // Parter
        require __DIR__ . '/partner.php';

        // Order History
        require __DIR__ . '/user_status_order.php';

        // Payment
        require __DIR__ . '/payment.php';

        // Transaction History
        require __DIR__ . '/transaction_history.php';

        // Notify
        require __DIR__ . '/notify.php';

        // Notification Histories
        require __DIR__ . '/notification_history.php';

        // Setting
        require __DIR__ . '/setting.php';

        // User Log
        require __DIR__ . '/user_log.php';

        // Membership Rank
        require __DIR__ . '/membership_rank.php';

        // Promotion
        require __DIR__ . '/promotion.php';

        // Product User
        require __DIR__ . '/product_user.php';

        // Banner
        require __DIR__ . '/banner.php';

        // App Menu
        require __DIR__ . '/app_menu.php';

        // Dashboard
        require __DIR__ . '/dashboard.php';

        // App Menu
        require __DIR__ . '/statistic.php';

        // Report
        require __DIR__ . '/report.php';

        // Report 2
        require __DIR__ . '/report_v3.php';

        // App Store
        require __DIR__ . '/store.php';

        // Contact Support
        require __DIR__ . '/contact_support.php';

        //Reply Contact Support
        require __DIR__ . '/reply_contact_support.php';

        //Cart
        require __DIR__ . '/cart.php';

        //Coupon
        require __DIR__ . '/coupon.php';

        require __DIR__ . '/promotion_program.php';

        // Company
        require __DIR__ . '/company.php';

        // Customer Group
        //        require __DIR__ . '/customer_group.php';

        // User Group
        require __DIR__ . '/user_group.php';

        // Collection
        require __DIR__ . '/collection.php';

        // File category
        require __DIR__ . '/file_category.php';

        // File cloud
        require __DIR__ . '/file_cloud.php';

        // Module
        require __DIR__ . '/module.php';

        // Menu
        require __DIR__ . '/menu.php';

        // Zalo
        require __DIR__ . '/zalo.php';

        // Active Omnichanel
        require __DIR__ . '/active_omnichanel.php';

        // Option
        require __DIR__ . '/catalog_option.php';

        // Unit
        require __DIR__ . '/unit.php';

        // Enterprise Order
        require __DIR__ . '/enterprise_order.php';

        // Area
        require __DIR__ . '/area.php';

        // Warehouse
        require __DIR__ . '/warehouse.php';


        // Order Status
        require __DIR__ . '/order_status.php';

        // Batch
        require __DIR__ . '/batch.php';

        // Shipping Address
        require __DIR__ . '/shipping_address.php';

        // Shipping Method
        require __DIR__ . '/shipping_method.php';

        // Feature
        require __DIR__ . '/feature.php';

        // Inventory
        require __DIR__ . '/inventory.php';

        // Warehouse_detail
        require __DIR__ . '/warehouse_detail.php';

        // Shipping Order
        require __DIR__ . '/shipping_order.php';

        // Product Comment
        require __DIR__ . '/product_comment.php';

        // Ship_order
        require __DIR__ . '/ship_order.php';

        // Payment Control Order
        require __DIR__ . '/payment_control_order.php';

        // Blog Post
        require __DIR__ . '/blog_post.php';

        // Attribute groups
        require __DIR__ . '/attribute_groups.php';

        // Attributes
        require __DIR__ . '/attributes.php';

        // Product attributes
        require __DIR__ . '/product_attributes.php';

        // Product variant
        require __DIR__ . '/product_variant.php';

        // Promotion Total
        require __DIR__ . '/promotion_total.php';

        // Zone Hub
        require __DIR__ . '/zone_hub.php';

        // Polls
        require __DIR__ . '/poll.php';

        // Prices
        require __DIR__ . '/price.php';

        // Sales Prices
        require __DIR__ . '/sale_price.php';

        // Website
        require __DIR__ . '/website.php';

        // Website
        require __DIR__ . '/website_theme.php';

        // Consultants
        require __DIR__ . '/consultant.php';

        // Videos Call Account
        require __DIR__ . '/video_call_account.php';

        require __DIR__ . '/product_favorite.php';

        // Call History
        require __DIR__ . '/call_history.php';

        // Issue
        require __DIR__ . '/issue.php';

        // Issue Module
        require __DIR__ . '/issue_module.php';

        // Issue Module Category
        require __DIR__ . '/issue_module_category.php';

        // Discuss
        require __DIR__ . '/discuss.php';

        // Brands
        require __DIR__ . '/brands.php';

        require __DIR__ . '/session.php';

        // IMPORT
        require __DIR__ . '/import.php';

        require __DIR__ . '/specification.php';
        require __DIR__ . '/promotion_ads.php';
        require __DIR__ . '/post.php';
        require __DIR__ . '/feedback.php';
        require __DIR__ . '/google_analytic.php';
        require __DIR__ . '/hashtag_comment.php';
        //Distributor
        require __DIR__ . '/distributor.php';

        require __DIR__ . '/rotation.php';
        require __DIR__ . '/region.php';
        require __DIR__ . '/age.php';
        require __DIR__ . '/manufacture.php';
        require __DIR__ . '/rotation_result.php';
        require __DIR__ . '/property.php';
        require __DIR__ . '/property_variant.php';
        require __DIR__ . '/product_info_dms_imports.php';
        require __DIR__ . '/product_hub.php';

        //Partner nutifood
        require __DIR__ . '/partner_nutifood.php';

        //form đăng kí mặt bằng
        require __DIR__ . '/rent_ground.php';
        //
        //AccessTrade
        require __DIR__ . '/access_trade_setting.php';

        require __DIR__ . '/sync_cdp.php';

        require __DIR__ . '/nndd.php';
        require __DIR__ . '/config_shipping.php';

    });
});
