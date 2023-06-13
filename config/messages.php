<?php
require_once('lang/attributes.php');
require_once('lang/message.php');
require_once('lang/normal_validate.php');
require_once('lang/result_validate.php');
require_once('lang/table_validate.php');
require_once('lang/type_or_status.php');

$auth = [
    'login_other' => [
        "EN" => 'User logged in from other place!',
        "VI" => "Tài khoản này đã đăng nhập ở thiết bị khác."
    ],
    'token_expired' => [
        "EN" => 'Token is expired',
        "VI" => "Token đã hết hạn"
    ],
    'token_invalid' => [
        "EN" => 'Token is invalid',
        "VI" => "Token không đúng"
    ],
    'bad_request' => [
        "EN" => 'Bad Request!',
        "VI" => "Yêu cầu thất bại"
    ],
    'unauthorized' => [
        "EN" => 'Unauthorized!',
        "VI" => "Chưa đăng nhập"
    ],
    'unknown' => [
        "EN" => 'Error: {0}',
        "VI" => "Ngoại lệ: {0}"
    ],
    'no_permission' => [
        "EN" => 'Permission Denied!',
        "VI" => 'Quyền truy cập bị chặn'
    ],
    'remote-denied' => [
        "EN" => 'Remote Connect is denied!',
        "VI" => 'Không được phép truy cập từ xa'
    ],
    'logout-success' => [
        "EN" => 'Logout Successfully!',
        "VI" => 'Đã đăng xuất'
    ],
];

$messages = array_merge($auth, $attributes, $message, $normal_validate, $result_validate, $table_validate,
    $type_or_status);

return $messages;