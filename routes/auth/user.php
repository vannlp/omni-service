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

$api->post('/logout', [
    'action' => '',
    'uses'   => 'UserController@logOut',
]);

$api->get('/users', [
    'action' => 'VIEW-USER',
    'uses'   => 'UserController@search',
]);

$api->get('/user-list', [
    'action' => 'VIEW-USER',
    'uses'   => 'UserController@searchList',
]);

$api->get('/users/{id:[0-9]+}', [
    'action' => 'VIEW-USER',
    'uses'   => 'UserController@view',
]);
$api->put('/users-status/{id:[0-9]+}', [
    'action' => 'VIEW-USER',
    'uses'   => 'UserController@updateStatus',
]);
$api->get('/users/profile', [
    'action' => '',
    'uses'   => 'UserController@viewProfile',
]);

$api->get('/users/info', [
    'action' => '',
    'uses'   => 'UserController@getInfo',
]);


$api->post('/users', [
    'action' => 'UPDATE-USER',
    'uses'   => 'UserController@create',
]);

$api->put('/users/profile', [
    // 'action' => 'UPDATE-USER',
    'uses'   => 'UserController@updateProfile',
]);

$api->put('/users/social-sync', [
    // 'action' => 'UPDATE-USER',
    'uses'   => 'UserController@socialSync',
]);

$api->put('/users/link-login', [
    // 'action' => 'UPDATE-USER',
    'uses'   => 'UserController@updateProfileLinkLogin',
]);

$api->put('/users/update-phone-profile', [
    'name' => 'CLIENT-CREATE-USER',
    'uses'   => 'UserController@updatePhoneUserSMS',
]);
$api->put('/users/update-phone-profile-otp', [
    'name' => 'CLIENT-CREATE-USER',
    'uses'   => 'UserController@updatePhoneUserOTP',
]);

$api->put('/users/change-my-profile', [
    'action' => '',
    'uses'   => 'UserController@changeMyProfile',
]);

$api->put('/users/change-password', [
    'action' => 'UPDATE-USER-PASSWORD',
    'uses'   => 'UserController@changePassword2',
]);

$api->put('/users/{id:[0-9]+}', [
    'action' => 'UPDATE-USER',
    'uses'   => 'UserController@update',
]);
$api->put('/users/{id:[0-9]+}/change-account-status', [
    'action' => 'UPDATE-USER',
    'uses'   => 'UserController@changeAccountStatus',
]);


$api->delete('/users/{id:[0-9]+}', [
    'action' => 'DELETE-USER',
    'uses'   => 'UserController@delete',
]);

$api->put('/users/{id:[0-9]+}/active', [
    'action' => 'UPDATE-USER',
    'uses'   => 'UserController@active',
]);

$api->put('/user-ready-work', [
    //'action' => 'UPDATE-USER',
    'uses' => 'UserController@readyWork',
]);

$api->get('/users/membership/{id:[0-9]+}', [
    //'action' => 'VIEW-MEMBER-SHIP',
    'uses' => 'UserController@membership',
]);

$api->get('/users/{user_id:[0-9]+}/get-point', [
    //'action' => 'VIEW-MEMBER-SHIP',
    'uses' => 'UserController@getPoint',
]);
$api->get('/users/get-my-point', [
    //'action' => 'VIEW-MEMBER-SHIP',
    'uses' => 'UserController@getMyPoint',
]);
$api->get('/users/get-my-rating', [
    //'action' => 'VIEW-MEMBER-SHIP',
    'uses' => 'UserController@getMyRating',
]);

$api->get('/users/{id:[0-9]+}/personal-income', [
    //'action' => 'VIEW-MEMBER-SHIP',
    'uses' => 'UserController@personalIncome',
]);

$api->get('/users/personal-income', [
    //'action' => 'VIEW-MEMBER-SHIP',
    'uses' => 'UserController@personalIncomePartner',
]);
$api->get('/users/get-my-payment-histories', [
    //'action' => 'VIEW-MEMBER-SHIP',
    'uses' => 'UserController@getMyPaymentHistory',
]);

$api->get('/users/order-statistic', [
    //    'action' => 'VIEW-DASHBOARD-ORDER',
    'action' => '',
    'uses'   => 'UserController@orderStatistic',
]);

$api->get('/users/list-partner-free-time', [
    'action' => '',
    'uses'   => 'UserController@listPartnerFreeTime',
]);

$api->get('/users/list-partner-working', [
    'action' => '',
    'uses'   => 'UserController@listPartnerWorking',
]);

$api->get('/users/partner-by-order/{orderId:[0-9]+}', [
    'action' => '',
    'uses'   => 'UserController@getPartnerByOrder',
]);

$api->get('/users/{id:[0-9]+}/tree', [
    'action' => 'VIEW-USER-TREE',
    'uses'   => 'UserController@viewTree',
]);

///////////////////////////// ACTIVE COMPANY ///////////////
$api->put('/users/set-active-company/{companyId:[0-9]+}', [
    //'action' => 'VIEW-COMPANY',
    'uses' => 'UserController@setActiveCompany',
]);

########################### NO AUTHENTICATION #####################
$api->get('/client/users/get-my-business-result', [
    'name'   => 'USER-VIEW-VIEW-BUSINESS',
    'action' => '',
    'uses'   => 'UserController@getClientBusinessResult'
]);

########################### GET USER HUB ##########################
$api->get('/get-user-hub/{id:[0-9]+}', [
    'action' => 'VIEW-USER-HUB',
    'uses'   => 'UserController@searchUserHub',
]);

$api->get('/client/get-list-user-hubs', [
    'name' => 'GET-CLIENT-USER',
    'uses' => 'UserController@clientGetListHub',
]);

########################## Get User Reference #####################
$api->get('/customers/reference', [
    'action' => 'VIEW-CUSTOMER',
    'uses'   => 'UserController@getUserReference',
]);
$api->get('/client/get-user-by-phone/{phone:[0-9]+}', [
    'name' => 'GET-CLIENT-USER',
    'uses' => 'UserController@getClientUserByPhone'
]);
$api->post('/client/create-password', [
    'name' => 'CLIENT-CREATE-USER',
    'uses' => 'UserController@createClientUserPassword'
]);
$api->get('/client/check-user-by-phone/{phone:[0-9]+}', [
    'name' => 'GET-CLIENT-USER',
    'uses' => 'UserController@checkClientPhoneExisted'
]);
$api->get('/client/check-existed-user-by-phone/{phone:[0-9]+}', [
    'name' => 'GET-CLIENT-USER',
    'uses' => 'UserController@checkClientPhoneExistedByUser'
]);
$api->get('/client/get-distributor-by-location/{city_code:[0-9]+}/{district_code:[0-9]+}/{ward_code:[0-9]+}', [
    'name' => 'GET-CLIENT-USER',
    'uses' => 'UserController@getClientDistributor'
]);
$api->get('/client/get-seller-by-location/{city_code:[0-9]+}/{district_code:[0-9]+}/{ward_code:[0-9]+}', [
    'name' => 'GET-CLIENT-USER',
    'uses' => 'UserController@getClientSeller'
]);

########################## Get User Order Status ##############################
$api->get('/user-order-status-summary', [
    'action' => '',
    'uses'   => 'UserController@getOderStatusSummary',
]);

$api->get('/user-all-order-status-summary', [
    'action' => '',
    'uses'   => 'UserController@getAllOderStatusSummary',
]);

$api->get('/print-agent-registration-form/{id:[0-9]+}', [
    'action' => '',
    'uses'   => 'UserController@printAgentRegistrationForm',
]);

############## Sync User ############333
$api->get('/sync/users', [
    'action' => '',
    'uses'   => 'UserController@syncUser',
]);

$api->get('personal-agent-certification', [
    'action' => '',
    'uses'   => 'UserController@personalAgentCertification',
]);

#############Expor Customers User Excel##################
$api->get('customers-export-excel', [
    'action' => '',
    'uses'   => 'UserController@customerExportExcel',
]);
$api->get('users-export-order-seller', [
    'action' => '',
    'uses'   => 'UserController@exportUserOrderBySeller',
]);
$api->get('users-export-order', [
    'action' => '',
    'uses'   => 'UserController@exportUserOrder',
]);
$api->get('users-export-order-by-location', [
    'action' => '',
    'uses'   => 'UserController@exportUserByLocation',
]);

$api->get('users-export-group', [
    'action' => '',
    'uses'   => 'UserController@exportUserGroup',
]);

$api->get('users-export', [
    'action' => '',
    'uses'   => 'UserController@exportUser',
]);

