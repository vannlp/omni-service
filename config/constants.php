<?php
define("SYNC_STATUS_NAME", [
    1 => "Đã duyệt",
    2 => "Đã giao hàng thành công",
    3 => "Hủy",
    4 => "Từ chối",
    5 => "Chưa xác nhận",
]);
define("STATUS_NAME_VIETTEL", [
    1 => "Chưa xác nhận",
    2 => "Đã xác nhận",
    3 => "Đã duyệt",
    4 => "Giao hàng thành công",
    5 => "Giao hàng thất bại",
    6 => "Hủy",
]);
define("SYNC_STATUS_NAME_VIETTEL", [
    1 => "NEW",
    2 => "APPROVED",
    3 => "SHIPPING",
    4 => "SHIPPED",
    5 => "RETURNED",
    6 => "CANCELED",
]);
define("LADING_METHOD", [
    "DEFAULT"  => "Mặc định",
    "economy"  => "Chuyển phát tiết kiệm",
    "standard" => "Chuyển phát nhanh",
    "express"  => "Chuyển phát hoả tốc",
    "save"     => "Chuyển phát tiêu chuẩn",
    "GHTC"     => "Chuyển phát tiêu chuẩn",
    "GHN"      => "Chuyển phát nhanh"
]);
define("PRODUCT_LENGTH_CLASS_CM", "Centimeter");
define("PRODUCT_LENGTH_CLASS_MM", "Millimeter");
define("PRODUCT_LENGTH_CLASS_IN", "Inch");
define("PRODUCT_UNIT_TYPE_PERCENT", "PERCENT");
define("PRODUCT_UNIT_TYPE_MONEY", "MONEY");

define("PRODUCT_IS_FEATURE", [
    "0" => "Không nổi bật",
    "1" => "Nổi bật",
]);

define("SHIP_STATUS_PENDING", "PENDING");
define("SHIP_STATUS_APPROVED", "APPROVED");
define("SHIP_STATUS_SHIPPING", "SHIPPING");
define("SHIP_STATUS_SHIPPED", "SHIPPED");
define("SHIP_STATUS_COMPLETED", "COMPLETED");
define("SHIP_STATUS_REJECTED", "REJECTED");
define("SHIP_STATUS_INVOICED", "INVOICED");
define("SHIP_STATUS_CANCELED", "CANCELED");
define("SHIP_STATUS_NAME", [
    "PENDING"   => "Đang chờ",
    "APPROVED"  => "Đã Approve",
    "APPROVED_2"  => "Đã Approve ver 2",
    "SHIPPING"  => "Đang giao hàng",
    "SHIPPED"   => "Đã giao hàng hoàn tất",
    "COMPLETED" => "Đã hoàn thành",
    "REJECTED"  => "Đã từ chối",
    "INVOICED"  => "Đã xuất hoá đơn",
    "CANCELED"  => "Đã hủy hóa đơn",
]);


define("PRODUCT_WEIGHT_CLASS_GR", "GRAM");
define("PRODUCT_WEIGHT_CLASS_KG", "KG");
define("IMAGE_PATH", "http://api-tm.kpis.vn/v0/img/");

define("WALLET_STATUS_RECHARGE", "RECHARGE");
define("WALLET_STATUS_WITHDRAW", "WITHDRAW");
define("WALLET_STATUS_OTHER", "OTHER");
define("WALLET_STATUS_NAME", [
    "RECHARGE" => "Nạp tiền",
    "WITHDRAW" => "Trừ tiền",
    "OTHER"    => "Khác",
]);

define("ORDER_STATUS_SELLER_COMPLETE", "confirmCOD");
define("ORDER_STATUS_SELLER_CANCELED", "cancel");
define("ORDER_STATUS_SELLER_CALLER1", "caller1");
define("ORDER_STATUS_SELLER_CALLER2", "caller2");
define("ORDER_STATUS_SELLER_CALLER3", "caller3");


define("USER_PASSWORD_DEFAULT", "tmshine-123456");
define("USER_ROLE_ADMIN", "ADMIN");
define("USER_ROLE_GUEST", "GUEST");
define("USER_ROLE_EMPLOYEE", "EMPLOYEE");
define("USER_ROLE_MANAGER", "Manager");
define("USER_ROLE_SUPERADMIN", "SUPERADMIN");
define("USER_ROLE_SHIPPER", "SHIPPER");
define("USER_ROLE_GUEST_ID", 4);
define("USER_ROLE_LEADER", "LEADER-CRM");
define("USER_ROLE_SELLER", "SELLER");
define("USER_ROLE_MONITOR", "MONITOR");
define("USER_ROLE_DISTRIBUTION", "DISTRIBUTION");

define("USER_ROLE_GROUP_ADMIN", "ADMIN");
define("USER_ROLE_GROUP_MANAGER", "MANAGER");
define("USER_ROLE_GROUP_EMPLOYEE", "EMPLOYEE");
define("USER_ROLE_GROUP_SELLER", "SELLER");
define("USER_ROLE_GROUP_SHIPPER", "SHIPPER");
define("USER_ROLE_GROUP_CUSTOMER", "CUSTOMER");
define("USER_ROLE_GROUP_GUEST", "GUEST");
define("USER_ROLE_GROUP_DISTRIBUTION", "DISTRIBUTION");

define("USER_TYPE_USER", "USER");
define("USER_TYPE_CUSTOMER", "CUSTOMER");
define("USER_TYPE_PARTNER", "PARTNER");
define("USER_TYPE_AGENT", "AGENT");
define("USER_TYPE_NAME", [
    "CUSTOMER" => "Khách hàng",
    "PARTNER"  => "Đối tác",
    "AGENT"    => "Đại lý",
]);
define("USER_GROUP_AGENT", "AGENT");
define("USER_GROUP_OUTLET", "OUTLET");
define("USER_GROUP_GUEST", "GUEST");
define("USER_GROUP_DISTRIBUTOR", "HUB"); // Hub
define("USER_GROUP_HUB", "DISTRIBUTOR"); // Nhà phân phối
define("USER_GROUP_DISTRIBUTOR_CENTER", "TTPP");

define("USER_PARTNER_TYPE_PERSONAL", "PERSONAL");
define("USER_PARTNER_TYPE_ENTERPRISE", "ENTERPRISE");
define("USER_PARTNER_TYPE_NAME", [
    USER_PARTNER_TYPE_PERSONAL   => "Đối tác cá nhân",
    USER_PARTNER_TYPE_ENTERPRISE => "Đối tác doanh nghiệp",
]);

define("PRODUCT_TYPE_PRODUCT", "PRODUCT");
define("PRODUCT_TYPE_SERVICE", "SERVICE");
define("PRODUCT_TYPE_NAME", [
    "PRODUCT" => "Sản phẩm",
    "SERVICE" => "Dịch vụ",
]);

define("ORDER_TYPE_AGENCY", "AGENCY");
define("ORDER_TYPE_GROCERY", "GROCERY");
define("ORDER_TYPE_GUEST", "GUEST");
define("ORDER_TYPE_CUSTOMER", "CUSTOMER");
define("ORDER_TYPE_NAME", [
    "AGENCY"   => "Đại lý",
    "GROCERY"  => "Tạp hoá",
    "GUEST"    => "Vãng Lai",
    "CUSTOMER" => "Khách hàng",
]);
define("ORDER_SOURCE_TYPE_ACCESS_TRADE", "AT");
define("ORDER_SOURCE_NAME", [
    "AT" => "AccessTrade",
    "GOOGLE" => 'GOOGLE',
    "TIKTOK" =>'TIKTOK',
    "WEBSITE" =>'WEBSITE'
]);
define("ORDER_TYPE_SYNC", [
    1 => "AGENCY",
    2 => "GROCERY",
    3 => "GUEST",
]);
define("DISCOUNT_ADMIN_TYPE_MONEY", 'money');
define("DISCOUNT_ADMIN_TYPE_PERCENTAGE", 'percentage');


define("ORDER_STATUS_NEW", "NEW");
define("ORDER_STATUS_RECEIVED", "RECEIVED");
define("ORDER_STATUS_IN_PROGRESS", "INPROGRESS");
define("ORDER_STATUS_COMPLETED", "COMPLETED");
define("ORDER_STATUS_CANCELED", "CANCELED");
define("ORDER_STATUS_APPROVED", "APPROVED");
define("ORDER_STATUS_RETURNED", "RETURNED");
define("ORDER_STATUS_SHIPPED", "SHIPPED");
define("ORDER_STATUS_SHIPPING", "SHIPPING");
define("ORDER_STATUS_PAID", "PAID");
define("ORDER_STATUS_ASSIGNED", "ASSIGNED");
define("ORDER_STATUS_PENDING", "PENDING");
define("ORDER_STATUS_REJECTED", "REJECTED");
define("ORDER_STATUS_NAME", [
    "NEW"        => "Mới",
    "RECEIVED"   => "Đã nhận",
    "INPROGRESS" => "Đang xử lý",
    "COMPLETED"  => "Đã hoàn thành",
    "CANCELED"   => "Đã hủy",
    "RETURNED"   => "Trả lại",
    "SHIPPED"    => "Đã giao hàng",
    "SHIPPING"   => "Đang giao hàng",
    "PAID"       => "Đã thanh toán",
    "ASSIGNED"   => "Chỉ định",
    "PENDING"    => "Đang chờ",
    "COMMING"    => "Đang đến",
    "ARRIVED"    => "Đã đến",
    "INPROGRESS" => "Đang thực hiện",
    "REJECTED"   => "Từ chối",
    "APPROVED"   => "Đã duyệt",
]);
define("ORDER_STATUS_CRM_APPROVED", "APPROVED");
define("ORDER_STATUS_CRM_ADAPPROVED", "ADAPPROVED"); // admin duyệt
define("ORDER_STATUS_CRM_PENDING", "PENDING");
define("ORDER_STATUS_CRM", [
    "APPROVED"    => "Đã duyệt đơn hàng",
    "PENDING"     => "Đang chờ xử lý",
    "ADAPPROVED"  => "Đã xác nhận đơn hàng",
]);
define("ORDER_STATUS_NEW_NAME", [
    "NEW"        => "Tiếp nhận đơn",
    "RECEIVED"   => "Đã nhận",
    "INPROGRESS" => "Đang xử lý",
    "COMPLETED"  => "Đã hoàn thành",
    "CANCELED"   => "Đã hủy",
    "RETURNED"   => "Trả lại",
    "SHIPPED"    => "Đã giao hàng",
    "SHIPPING"   => "Xử lý giao hàng",
    "PAID"       => "Đã thanh toán",
    "ASSIGNED"   => "Chỉ định",
    "PENDING"    => "Đang chờ",
    "COMMING"    => "Đang đến",
    "ARRIVED"    => "Đã đến",
    "INPROGRESS" => "Đang thực hiện",
    "REJECTED"   => "Từ chối",
    "APPROVED"   => "Xác nhận đơn",
]);


define("NEXT_STATUS_ORDER", [
    "NEW"        => "RECEIVED",
    "PENDING"    => "RECEIVED",
    "RECEIVED"   => "COMMING",
    "COMMING"    => "ARRIVED",
    "ARRIVED"    => "INPROGRESS",
    "INPROGRESS" => "COMPLETED",
    "COMPLETED"  => "COMPLETED",
    "CANCELED"   => "CANCELED",
    "RETURNED"   => "RETURNED",
    "SHIPPED"    => "SHIPPED",
    "SHIPPING"   => "SHIPPING",
    "PAID"       => "PAID",
    "ASSIGNED"   => "ASSIGNED",
]);

define("ORDER_STATUS_NEXT", [
    "NEW"        => "APPROVED",
    "APPROVED"   => "SHIPPING",
    "SHIPPING"   => "SHIPPED",
    "SHIPPED"    => "COMPLETED",
]);

define("ISSUE_STATUS_NAME", [
    "NEW"         => "Mới",
    "IN-PROGRESS" => "Đang tiến hành",
    "SOLVED"      => "Đã giải quyết",
    "OPENED"      => "Mở lại ",
    "COMPLETED"   => "Đã hoàn thành",
]);

define("ISSUE_PRIORITY_NAME", [
    "HIGHEST" => "Rất cao",
    "HIGH"    => "Cao",
    "MEDIUM"  => "Trung bình",
    "LOW"     => "Thấp",
    "LOWEST"  => "Rất thấp",
]);

define("INVENTORY_STATUS_COMPLETED", "COMPLETED");
define("INVENTORY_STATUS_PENDING", "PENDING");
define("INVENTORY_CODE_PREFIX", [
    "0" => "X",
    "1" => "N",
]);
define("INVENTORY_STATUS_NAME", [
    "PENDING"   => "Chưa Hoàn Thành",
    "COMPLETED" => "Đã hoàn thành",
]);
define("INVENTORY_TYPE_N", 1);
define("INVENTORY_TYPE_X", 0);

define("STATUS_ACCOUNT_USER", [
    "0" => "Chưa kích hoạt",
    "1" => "Hoạt động",
]);
define("COUPON_CODE_TYPE", [
    "Giảm giá tiền" => "F",
    "Giảm giá %" => "P",
]);

define("STATUS_WAREHOUSE_DETAILS", [
    "0" => "Hết hàng",
    "1" => "Còn hàng",
]);

define("VERIFIED", [
    "0" => "Chưa xác thực",
    "1" => "Đã xác thực",
]);

define("CONTACT_CATEGORY_NAME", [
    "ACCOUNT"       => "Tài khoản",
    "SUPPORT_ORDER" => "Hổ trợ đơn hàng",
    "PAYMENT"       => "Thanh toán",
    "OTHER"         => "Khác",
]);
define("ORDER_HISTORY_STATUS_COMMING", "COMMING");
define("ORDER_HISTORY_STATUS_ARRIVED", "ARRIVED");
define("ORDER_HISTORY_STATUS_INPROGRESS", "INPROGRESS");
define("ORDER_HISTORY_STATUS_COMPLETED", "COMPLETED");
define("ORDER_HISTORY_STATUS_PENDING", "PENDING");
define("ORDER_HISTORY_STATUS_NAME", [
    "COMMING"    => "Đang đến",
    "ARRIVED"    => "Đã đến",
    "INPROGRESS" => "Đang thực hiện",
    "COMPLETED"  => "Đã hoàn thành",
    "PENDING"    => "Đang chờ",
]);
define("CONTACT_SUPPORT_STATUS_NEW", "NEW");
define("CONTACT_SUPPORT_STATUS_RECEIVED", "RECEIVED");
define("CONTACT_SUPPORT_STATUS_SOLVED", "SOLVED");
define("CONTACT_STATUS_NAME", [
    "NEW"      => "Mới",
    "RECEIVED" => "Đã nhận",
    "SOLVED"   => "Đã giải quyết",
]);

define("BLOG_POST_STATUS_NAME", [
    "waiting_approval" => "Chờ duyệt",
    "draft"            => "Nháp",
    "archived"         => "Lưu trữ",
    "published"        => "Xuất bản",
    "trashed"          => "Dừng xuất bản",
    "closed"           => "Đóng",
    "waiting_publish"  => "Chờ xuất bản",
]);


define("ACCOUNT_STATUS_PENDING", "pending");
define("ACCOUNT_STATUS_REJECTED", "rejected");
define("ACCOUNT_STATUS_APPROVED", "approved");
define("ACCOUNT_STATUS_NAME", [
    "pending"  => "Đang chờ kích hoạt",
    "rejected" => "Đã bị từ chối",
    "approved" => "Đã kích hoạt",
]);

define("CUSTOMER_TYPE_PARTNER", "partner");

####################################### FOR PAYMENT #################################
define("PAYMENT_METHOD_ONLINE", "ONLINE");
define("PAYMENT_METHOD_CASH", "CASH");

define("PAYMENT_TYPE_RECHARGE", "RECHARGE");
define("PAYMENT_TYPE_WITHDRAW", "WITHDRAW");
define("PAYMENT_TYPE_PAYMENT", "PAYMENT");
define("PAYMENT_TYPE_NAME", [
    "RECHARGE" => "Nạp tiền",
    "WITHDRAW" => "Rút tiền",
    "PAYMENT"  => "Thanh toán đơn hàng",
]);
define("PAYMENT_METHOD_WALLET", "WALLET");
define("PAYMENT_METHOD_MOMO", "MOMO");
define("PAYMENT_METHOD_ONEPAY", "ONEPAY");
define("PAYMENT_METHOD_ZALO", "ZALO");
define("PAYMENT_METHOD_SPP", "SPP");
define("PAYMENT_METHOD_VPB", "VPB");
define("PAYMENT_METHOD_VNPAY", "VNPAY");
define("PAYMENT_METHOD_BANK", "bank_transfer");
define("PAYMENT_METHOD_QUERY", [
    "SPP",
    "MOMO",
    "ZALO",
    "bank_transfer"
]);
define("PAYMENT_METHOD_NAME", [
    "VPB"            => "Thanh toán qua thẻ tín dụng/thẻ ghi nợ quốc tế",
    "WALLET"         => "Ví tiền",
    "SPP"            => "ShopeePay",
    "MOMO"           => "Ví MoMo",
    "ONEPAY"         => "OnePay",
    "ZALO"           => "ZaloPay",
    "VNPAY"          => "VnPay",
    "CASH"           => "Tiền mặt",
    "bank_transfer"  => "Chuyển khoản qua ngân hàng",
    "COD"            => "Tiền mặt",
    "online_payment" => "Thanh toán online",
]);

define("PAYMENT_STATUS_FAIL", "FAIL");
define("PAYMENT_STATUS_SUCCESS", "SUCCESS");
define("PAYMENT_STATUS_NAME", [
    "FAIL"    => "Thất bại",
    "SUCCESS" => "Thành công",
]);
define("ORDER_CHANNEL_WEB", "WEB");

define("PAYMENT_METHOD_NAME_CONVERT", [
    "bank_transfer"  => "Chuyển khoản",
    "cod"            => "Thu hộ",
    "cash"           => "Tiền mặt",
    "online_payment" => "Thanh toán online",
]);

define("PUBLISH_STATUS_NAME", [
    "draft"            => "Nháp",
    "waiting_approval" => "Chờ duyệt",
    "approved"         => "Đã duyệt",
    "rejected"         => "Từ chối duyệt",
]);

define("RATE_NAME", [
    ""  => null,
    "1" => "Không thích",
    "2" => "Tạm được",
    "3" => "Bình thường",
    "4" => "Rất tốt",
    "5" => "Tuyệt vời quá",
]);
define("CODE_SHOPPING_PAYMENT", "NTF");
define("PRODUCT_COMMENT_TYPE_RATE", "RATE");
define("PRODUCT_COMMENT_TYPE_QAA", "QAA");
define("PRODUCT_COMMENT_TYPE_REPLY", "REPLY");
define("PRODUCT_COMMENT_TYPE_COMMENT", "COMMENT");
define("PRODUCT_COMMENT_TYPE_REPLY_COMMENT", "REPLY_COMMENT");
define("PRODUCT_COMMENT_TYPE_QUESTION", "QUESTION");
define("PRODUCT_COMMENT_TYPE_RATESHIPPING", "RATESHIPPING");
define("PRODUCT_COMMENT_TYPE_NAME", [
    "RATE"          => "Bình luận, đánh giá sản phẩm",
    "REPLY"         => "Trả lời đánh giá",
    "COMMENT"       => "Bình luận đánh giá",
    "REPLY_COMMENT" => "Phản hồi bình luận",
    "QAA"           => "Hỏi đáp sản phẩm",
    "QUESTION"      => "Bình luận, đánh giá sản phẩm",
    "RATESHIPPING"  => "Đánh giá đơn vị vận chuyển",
]);

define("ONEPAY_CODE_MSG", [
    0   => "Giao dịch thành công",
    1   => "Giao dịch không thành công. Ngân hàng phát hành thẻ từ chối cấp phép cho giao dịch. Vui lòng liên hệ ngân hàng theo số điện thoại sau mặt thẻ để biết chính xác nguyên nhân Ngân hàng từ chối.",
    3   => "Giao dịch không thành công. có lỗi trong quá trình cài đặt cổng thanh toán. Vui lòng liên hệ với OnePAY để được hỗ trợ (Hotline 1900 633 927)",
    4   => "Giao dịch không thành công. có lỗi trong quá trình cài đặt cổng thanh toán. Vui lòng liên hệ với OnePAY để được hỗ trợ (Hotline 1900 633 927)",
    5   => "Giao dịch không thành công. số tiền không hợp lệ. Vui lòng liên hệ với OnePAY để được hỗ trợ (Hotline 1900 633 927)",
    6   => "Giao dịch không thành công. loại tiền tệ không hợp lệ. Vui lòng liên hệ với OnePAY để được hỗ trợ (Hotline 1900 633 927)",
    7   => "Giao dịch không thành công. Ngân hàng phát hành thẻ từ chối cấp phép cho giao dịch. Vui lòng liên hệ ngân hàng theo số điện thoại sau mặt thẻ để biết chính xác nguyên nhân Ngân hàng từ chối.",
    8   => "Giao dịch không thành công. Số thẻ không đúng. Vui lòng kiểm tra và thực hiện thanh toán lại",
    9   => "Giao dịch không thành công. Tên chủ thẻ không đúng. Vui lòng kiểm tra và thực hiện thanh toán lại",
    10  => "Giao dịch không thành công. Thẻ hết hạn/Thẻ bị khóa. Vui lòng kiểm tra và thực hiện thanh toán lại",
    11  => "Giao dịch không thành công. Thẻ chưa đăng ký sử dụng dịch vụ thanh toán trên Internet. Vui lòng liên hê ngân hàng theo số điện thoại sau mặt thẻ để được hỗ trợ.",
    12  => "Giao dịch không thành công. Ngày phát hành/Hết hạn không đúng. Vui lòng kiểm tra và thực hiện thanh toán lại",
    13  => "Giao dịch không thành công. thẻ/ tài khoản đã vượt quá hạn mức thanh toán. Vui lòng kiểm tra và thực hiện thanh toán lại",
    21  => "Giao dịch không thành công. Số tiền không đủ để thanh toán. Vui lòng kiểm tra và thực hiện thanh toán lại",
    22  => "Giao dịch không thành công. Thông tin tài khoản không đúng. Vui lòng kiểm tra và thực hiện thanh toán lại",
    23  => "Giao dịch không thành công. Tài khoản bị khóa. Vui lòng liên hê ngân hàng theo số điện thoại sau mặt thẻ để được hỗ trợ",
    24  => "Giao dịch không thành công. Thông tin thẻ không đúng. Vui lòng kiểm tra và thực hiện thanh toán lại",
    25  => "Giao dịch không thành công. OTP không đúng. Vui lòng kiểm tra và thực hiện thanh toán lại",
    253 => "Giao dịch không thành công. Quá thời gian thanh toán. Vui lòng thực hiện thanh toán lại",
    99  => "Giao dịch không thành công. Người sử dụng hủy giao dịch",
]);

define("MOMO_CODE_MSG", [
    '0'    => 'Success',
    '-1'   => 'Transaction not exist or wrong format',
    '3'    => 'Agent not found',
    '4'    => 'Agent not registered',
    '6'    => 'Agent suspended',
    '7'    => 'Access denied',
    '8'    => 'Bad password',
    '9'    => 'Password expired',
    '11'   => 'Password retry exceed',
    '13'   => 'Target not found',
    '14'   => 'Target not registered',
    '17'   => 'Invalid amount',
    '23'   => 'Amount too small',
    '27'   => 'Agent expired',
    '29'   => 'Invalid password',
    '31'   => 'User not exist in MoMo system',
    '32'   => 'Request expired',
    '33'   => 'Transaction reversed',
    '34'   => 'Transaction error',
    '45'   => 'In timeout',
    '46'   => 'Amount out of range',
    '47'   => 'Target is not special',
    '48'   => 'Billpay account invalid',
    '151'  => 'Amount not in range from [A] to [B]',
    '153'  => 'Decrypt hash data fail',
    '161'  => 'Payment capset limited by user. Current user payment capset is [X]. Please contact MoMo customer support for assistance',
    '162'  => 'The payment code has been used',
    '208'  => 'Partner is not active',
    '210'  => 'Service maintenance',
    '403'  => 'Permission denied',
    '404'  => 'Request not found',
    '1001' => 'Insufficient funds',
    '1002' => 'Transaction recovered',
    '1003' => 'Wallet Balance Exceeded',
    '1004' => 'Wallet Cap Exceeded',
    '1006' => 'System Error',
    '1007' => 'DB Error',
    '1008' => 'Bad request',
    '1013' => 'Auth expired',
    '1014' => 'Wrong password',
    '1020' => 'Invalid param required',
    '2101' => 'User not login',
    '2111' => 'The payment information was missing',
    '2117' => 'Bill expired',
    '2125' => 'Cannot refund transaction',
    '2126' => 'Partner is not config',
    '2127' => 'Partner config not found or wrong format',
    '2128' => 'Duplicate request ID',
    '2129' => 'Signature not match',
    '2131' => 'Bill not exist or expired',
    '2132' => 'Transaction processed',
    '2135' => 'Partner payment fail',
    '2137' => 'Partner account had linked',
    '2138' => 'User session has been expired',
    '2400' => 'Bad format data',
    '9000' => 'Transaction processing',
]);
define("SHOPEE_PAY_STATUS", [
    "2" => "Transaction processing",
    "3" => "Transaction successful",
    "4" => "Transaction failed ",
]);
define("ORDER_SHIP_FEE", [
    "DIST-760" => 20000,
]);

define("ZALO_PAYMENT_METHOD", [
    "0" => "Khác",
    "1" => "COD",
    "2" => "CreditCard",
    "3" => "DomesticATM",
    "4" => "NganLuong",
    "5" => "ZaloPay",
    "6" => "Napas",
    "7" => "VNPay",
    "8" => "OnePay",
]);

define("ZALO_ORDER_STATUS", [
    "1" => "NEW",
    "2" => "INPROGRESS",
    "3" => "RECEIVED",
    "4" => "SHIPPING",
    "5" => "COMPLETED",
    "6" => "CANCELED",
    "7" => "RETURNED",
]);

define("TYPE_SYNC_ORDER", "sync_order");
define("TYPE_SYNC_PRODUCT", "sync_product");

define("OMNI_CHANEL_ZALO", "ZAL");
define("OMNI_CHANEL_SHOPEE", "SHO");
define("OMNI_CHANEL_LAZADA", "LAZ");
define("OMNI_CHANEL_TIKI", "TIK");
define("OMNI_CHANEL_CODE", [
    OMNI_CHANEL_ZALO   => "Zalo",
    OMNI_CHANEL_SHOPEE => "Shopee",
    OMNI_CHANEL_LAZADA => "Lazada",
    OMNI_CHANEL_TIKI   => "Tiki",
]);

define("SHIPPING_PARTNER_TYPE_GHTK", "GHTK");
define("SHIPPING_PARTNER_TYPE_GHN", "GHN");
define("SHIPPING_PARTNER_TYPE_AHA", "AHA");
define("SHIPPING_PARTNER_TYPE_NJV", "NJV");
define("SHIPPING_PARTNER_TYPE_VNP", "VNP");
define("SHIPPING_PARTNER_TYPE_VTP", "VIETTELPOST");
define("SHIPPING_PARTNER_TYPE_GRAB", "GRAB");
define("SHIPPING_PARTNER_TYPE_DEFAULT", "DEFAULT");
define("SHIPPING_PARTNER_TYPE_NAME", [
    SHIPPING_PARTNER_TYPE_DEFAULT        => "Nutifood tự giao",
    SHIPPING_PARTNER_TYPE_GRAB           => "Grab Express",
    SHIPPING_PARTNER_TYPE_VTP            => "Viettel Post",
]);
define("PROMOTION_TYPE_AUTO", "AUTO");
define("PROMOTION_TYPE_CODE", "CODE");
define("PROMOTION_TYPE_DISCOUNT", "DISCOUNT");
define("PROMOTION_TYPE_COMMISSION", "COMMISSION");
define("PROMOTION_TYPE_POINT", "POINT");
define("PROMOTION_TYPE_FLASH_SALE", "FLASH_SALE");
define("PROMOTION_TYPE_GIFT", "GIFT");
define("PROMOTION_TYPE_BUY_X_GET_Y", "buy_x_get_y");
define("PROMOTION_TYPE_NAME", [
    "AUTO"        => "Khuyến mãi tự động",
    "CODE"        => "Mã khuyến mãi",
    "DISCOUNT"    => "Chiết khấu",
    "COMMISSION"  => "Hoa hồng",
    "POINT"       => "Tích điểm",
    "FLASH_SALE"  => "Flash Sale",
    "GIFT"        => "Quà tặng",
    "buy_x_get_y" => "Mua x tặng y",
]);
define("TYPE_PROMOTION_PRODUCT", "PRODUCT");
define("TYPE_PROMOTION_CART", "CART");
define("TYPE_PROMOTION_NAME", [
    "PRODUCT" => "Sản phẩm",
    "CART"    => "Giỏ hàng",
]);
define("GENDER_CUSTOMER", [
    "O" => "Khác",
    "F" => "Nam",
    "M" => "Nữ",
]);

return [
    'URL_IMG'                   => 'Media/IMG',
    'STATUS'                    => [
        'GENE' => [
            'M' => 'Male',
            'F' => 'Female',
            'O' => 'Other',
        ],
    ],
    'url'                       => [
    ],
    'customer_password_default' => 'TM@123456',
    'EXCEL'                     => [
        'CHAR' => [
            "",
            "A",
            "B",
            "C",
            "D",
            "E",
            "F",
            "G",
            "H",
            "I",
            "J",
            "K",
            "L",
            "M",
            "N",
            "O",
            "P",
            "Q",
            "R",
            "S",
            "T",
            "U",
            "V",
            "W",
            "X",
            "Y",
            "Z",

            "AA",
            "AB",
            "AC",
            "AD",
            "AE",
            "AF",
            "AG",
            "AH",
            "AI",
            "AJ",
            "AK",
            "AL",
            "AM",
            "AN",
            "AO",
            "AP",
            "AQ",
            "AR",
            "AS",
            "AT",
            "AU",
            "AV",
            "AW",
            "AX",
            "AY",
            "AZ",

            "BA",
            "BB",
            "BC",
            "BD",
            "BE",
            "BF",
            "BG",
            "BH",
            "BI",
            "BJ",
            "BK",
            "BL",
            "BM",
            "BN",
            "BO",
            "BP",
            "BQ",
            "BR",
            "BS",
            "BT",
            "BU",
            "BV",
            "BW",
            "BX",
            "BY",
            "BZ",

            "CA",
            "CB",
            "CC",
            "CD",
            "CE",
            "CF",
            "CG",
            "CH",
            "CI",
            "CJ",
            "CK",
            "CL",
            "CM",
            "CN",
            "CO",
            "CP",
            "CQ",
            "CR",
            "CS",
            "CT",
            "CU",
            "CV",
            "CW",
            "CX",
            "CY",
            "CZ",
        ],
    ],
    define('MIME_FILE_TYPE', [
            'txt'  => 'FILE',
            'htm'  => 'FILE',
            'html' => 'FILE',
            'php'  => 'FILE',
            'css'  => 'FILE',
            'js'   => 'FILE',
            'json' => 'FILE',
            'xml'  => 'FILE',
            'swf'  => 'FILE',
            'flv'  => 'VIDEO',
            'mp4'  => 'VIDEO',

            'png'  => 'IMAGE',
            'jpe'  => 'IMAGE',
            'jpeg' => 'IMAGE',
            'jpg'  => 'IMAGE',
            'gif'  => 'IMAGE',
            'bmp'  => 'IMAGE',
            'ico'  => 'IMAGE',
            'tiff' => 'IMAGE',
            'tif'  => 'IMAGE',
            'svg'  => 'IMAGE',
            'svgz' => 'IMAGE',
            'jfif' => 'IMAGE',
            'webp' => 'IMAGE',

            'zip' => 'FILE',
            'rar' => 'FILE',
            'exe' => 'FILE',
            'msi' => 'FILE',
            'cab' => 'FILE',

            'mp3'      => 'AUDIO',
            'wave'     => 'AUDIO',
            'wav'      => 'AUDIO',
            'x-wav'    => 'AUDIO',
            'x-pn-wav' => 'AUDIO',
            'ogg'      => 'AUDIO',
            'qt'       => 'VIDEO',
            'mov'      => 'VIDEO',

            'pdf' => 'FILE',
            'psd' => 'FILE',
            'ai'  => 'FILE',
            'eps' => 'FILE',
            'ps'  => 'FILE',

            'doc' => 'FILE',
            'rtf' => 'FILE',
            'xls' => 'FILE',
            'ppt' => 'FILE',

            'odt' => 'FILE',
            'ods' => 'FILE',
        ]
    ),
    // Foreign Tables
    'FT'                        => [
        'users'             => [
            'profiles.user_id' => 'Profile',
        ],
        'permission_groups' => ['permissions.group_id' => 'Permission'],
        'permissions'       => ['role_permissions.permission_id' => 'Role Permission'],
        'roles'             => ['role_permissions.role_id' => 'Role Permission'],
    ],
];
