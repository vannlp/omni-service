<?php

/**
 * User: Ho Sy Dai
 * Date: 10/25/2018
 * Time: 11:03 AM
 */

$table_validate = [
    # Users
    "users"                                  => [
        "EN" => "User",
        "VI" => "Tài khoản",
    ],
    "users.fk"                               => [
        "EN" => "Can not delete [{0}] because using in plants",
        "VI" => "[{0}] không thể xóa do đang sử dụng trong danh sách cây trồng",
    ],
    "users.login-invalid"                    => [
        "EN" => "Phone not password is invalid!",
        "VI" => "Số điện thoại hoặc mật khẩu không đúng!",
    ],
    "users.admin-login-invalid"              => [
        "EN" => "User not password is invalid!",
        "VI" => "Tên đăng nhập hoặc mật khẩu không đúng!",
    ],
    "users.user-inactive"                    => [
        "EN" => "The user is inactive",
        "VI" => "Tài khoản chưa được kích hoạt",
    ],
    "users.login-not-exist"                  => [
        "EN" => "[{0}] is not exist or not activate.",
        "VI" => "[{0}] chưa được đăng ký hoặc chưa kích hoạt.",
    ],
    "users.not-allow-access"                 => [
        "EN" => "[{0}] is not allow to access.",
        "VI" => "[{0}] không được phép.",
    ],
    "users.not-exist"                        => [
        "EN" => "[{0}] not exist",
        "VI" => "[{0}] không tồn tại",
    ],
    "users.existed"                          => [
        "EN" => "Culture of [{0}] not exist",
        "VI" => "Thông tin canh tác của [{0}] không tồn tại",
    ],
    "users.create-success"                   => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "users.update-success"                   => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "users.link-login-success"                   => [
        "EN" => "you have successfully linked [{0}] account",
        "VI" => "bạn vừa liên kết tài khoản [{0}] thành công",
    ],
    "users.update-password-success"          => [
        "EN" => "Has updated password successful",
        "VI" => "Vừa Cập nhật mật khẩu thành công",
    ],
    "users.change-password"                  => [
        "EN" => "You have just changed the password successfully",
        "VI" => "Bạn vừa thay đổi mật khẩu thành công",
    ],
    "users.is-vail-password"                  => [
        "EN" => "The password is the same as the old password",
        "VI" => "Mật khẩu trùng với mật khẩu cũ",
    ],
    "users.delete-success"                   => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],
    "users.register-success"                 => [
        "EN" => "[{0}] has register successful",
        "VI" => "[{0}] đã được đăng ký thành công",
    ],
    "users.register-success-wait-active"     => [
        "EN" => "[{0}] has register successful. Thank you for registering to be a Partner. Your account will be reviewed within 24 hours",
        "VI" => "[{0}] đã được đăng ký thành công. Cám ơn bạn đã đăng ký trở thành Partner. Tài khoản của bạn sẽ được xét duyệt trong vòng 24h",
    ],
    "users.phone-exits"                      => [
        "EN" => "Phone number not exits !",
        "VI" => "Số điện thoại không tồn tại !",
    ],
    "users.opt-send-successfully"                      => [
        "EN" => "The OTP has been sent successfully! Please check your message.",
        "VI" => "Mã OTP đã được gửi thành công! Vui lòng kiểm tra tin nhắn",
    ],
    "users.active-success"                   => [
        "EN" => "Account [{0}] has activated successful",
        "VI" => "Tài khoản [{0}] vừa được kích hoạt thành công",
    ],
    "users.inactive-success"                 => [
        "EN" => "Account [{0}] has inactivated successful",
        "VI" => "Tài khoản [{0}] vừa được vô hiệu hóa thành công",
    ],
    "user_profiles.not-exist"                => [
        "EN" => "Profile user [{0}] not exist",
        "VI" => "Profile của người dùng [{0}] không tồn tại",
    ],
    "user_profiles.profile-inactive-success" => [
        "EN" => "You have disabled receiving orders.",
        "VI" => "Bạn đã tắt nhận đơn.",
    ],
    "user_profiles.profile-active-success"   => [
        "EN" => "You are ready to receive orders.",
        "VI" => "Bạn đã sẵn sàng nhận đơn.",
    ],
    "user_profiles.profile-change-error"     => [
        "EN" => "Change status error.",
        "VI" => "Thay đổi trạng thái thất bại.",
    ],
    # Roles
    "roles"                                  => [
        "EN" => "Roles",
        "VI" => "Vai trò",
    ],
    "roles.fk"                               => [
        "EN" => "Can not delete [{0}] because using in roles",
        "VI" => "[{0}] không thể xóa do đang sử dụng trong danh sách các vai trò của ứng dụng",
    ],
    "roles.create-success"                   => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "roles.update-success"                   => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "roles.delete-success"                   => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    "companies"                         => [
        "EN" => "Company",
        "VI" => "Công ty",
    ],

    # Permission
    "permissions.fk"                    => [
        "EN" => "Can not delete [{0}] because using in plant bug",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "permissions.create-success"        => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "permissions.update-success"        => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "permissions.delete-success"        => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Products
    "products"                          => [
        "EN" => "Products",
        "VI" => "Sản phẩm",
    ],
    "products.fk"                       => [
        "EN" => "Can not delete [{0}] because using in products",
        "VI" => "[{0}] không thể xóa do đang sử dụng trong danh sách sản phẩm",
    ],
    "products.existed"                  => [
        "EN" => "Product {0} existed",
        "VI" => "Sản phẩm [{0}] không thể tạo hoặc chỉnh sửa do thông tin bị trùng",
    ],
    "products.create-success"           => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "products.update-success"           => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "products.delete-success"           => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa sản phẩm [{0}] thành công",
    ],
    "products.review-success"           => [
        "EN" => "[{0}] has review successful",
        "VI" => "Bạn vừa đánh giá [{0}] thành công",
    ],
    #user group
    "user_group"             => [
        "EN" => "User group",
        "VI" => "Nhóm người dùng",
    ],
    "user_group.not-exist"             => [
        "EN" => "The group [{0}] doesn't exist",
        "VI" => "Nhóm [{0}] không tồn tại",
    ],
    #Distributor
    "distributor.address-not-exist"             => [
        "EN" => "Distributor address already exists",
        "VI" => "Địa chỉ nhà phân phối đã tồn tại",
    ],
    "distributor.hub-not-exist"             => [
        "EN" => "The distributor [{0}] does not exist.",
        "VI" => "Nhà phân phối [{0}] không tồn tại",
    ],
    # Categories
    "categories"                        => [
        "EN" => "Product Category",
        "VI" => "Danh mục sản phẩm",
    ],
    "categories.fk"                     => [
        "EN" => "Can not delete [{0}] because using in categories",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "categories.existed"                => [
        "EN" => "Category {0} existed",
        "VI" => "Danh mục [{0}] không thể tạo hoặc chỉnh sửa do thông tin bị trùng",
    ],
    "categories.create-success"         => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "categories.update-success"         => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "categories.delete-success"         => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],
    "categories.import-success"         => [
        "EN" => "[{0}] categories has imported successful",
        "VI" => "[{0}] danh mục vừa được import thành công",
    ],

    # Master Data Type
    "master_data_type"                  => [
        "EN" => " Master Data Type",
        "VI" => " Kiểu dữ liệu chủ",
    ],
    "master_data_type.fk"               => [
        "EN" => "Can not delete [{0}] because using in master_data_type",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "master_data_type.create-success"   => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "master_data_type.update-success"   => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "master_data_type.delete-success"   => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Master Data
    "master_data"                       => [
        "EN" => " Master Data ",
        "VI" => "Dữ liệu chủ",
    ],
    "master_data.fk"                    => [
        "EN" => "Can not delete [{0}] because using in master_data",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "master_data.create-success"        => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "master_data.update-success"        => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "master_data.delete-success"        => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Card
    "cards.fk"                          => [
        "EN" => "Can not delete [{0}] because using in plant bug",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "cards.create-success"              => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "cards.update-success"              => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "cards.delete-success"              => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    #Department
    "department_data"                   => [
        "EN" => " Master Data ",
        "VI" => "Dữ liệu chủ",
    ],
    "department.fk"                     => [
        "EN" => "Can not delete [{0}] because using in master_data",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "department.create-success"         => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "department.update-success"         => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "department.delete-success"         => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Folders
    "folders.fk"                        => [
        "EN" => "Can not delete [{0}] because using in master_data",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "folders.create-success"            => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "folders.update-success"            => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "folders.delete-success"            => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Orders
    "orders"                            => [
        "EN" => "Order",
        "VI" => "Đơn hàng",
    ],
    "order_details"                     => [
        "EN" => "Order Detail",
        "VI" => "Chi tiết sản phẩm",
    ],
    "orders.fk"                         => [
        "EN" => "Can not delete [{0}] because using in orders",
        "VI" => "[{0}] không thể xóa do đang sử dụng trong danh sách đơn hàng",
    ],
    "orders.not-exist"                  => [
        "EN" => "Order #{0} not exist",
        "VI" => "Đơn hàng #{0} không tồn tại",
    ],
    "orders.existed"                    => [
        "EN" => "Order #{0} does exist",
        "VI" => "Đơn hàng #{0} đã tồn tại",
    ],
    "orders.free_ship"                  => [
        "EN" => "Please don't input ship fee because order #{0} is free ship",
        "VI" => "Vui lòng không nhập phí ship vì đơn hàng #{0} miễn phí vận chuyển",
    ],
    "orders.create-success"             => [
        "EN" => "Order #{0} has created successful",
        "VI" => "Đơn hàng #{0} vừa được tạo thành công",
    ],
    "orders.update-success"             => [
        "EN" => "Order #{0} has updated successful",
        "VI" => "Đơn hàng #{0} vừa được chỉnh sửa thành công",
    ],
    "orders.payment-unpaid"             => [
        "EN" => "Online payment order has not been paid",
        "VI" => "Đơn hàng thanh toán online chưa được thanh toán.",
    ],
    "orders.update-seller-success"             => [
        "EN" => "Order successful allocation",
        "VI" => "Phân bổ đơn hàng thành công",
    ],

    "orders.update-collection-success"             => [
        "EN" => "Order recovery successful",
        "VI" => "Thu hồi đơn hàng thành công",
    ],
    "orders.update-block"               => [
        "EN" => "Order #{0} deny update because has completed",
        "VI" => "Đơn hàng #{0} không được cập nhật do đã hoàn tất",
    ],
    "orders.delete-success"             => [
        "EN" => "Order #{0} has deleted",
        "VI" => "Bạn vừa xóa đơn hàng #{0} thành công",
    ],
    "orders.not-distributor"                  => [
        "EN" => "Order #{0} not an application from the NPP",
        "VI" => "Đơn hàng #{0} không phải đơn của NPP",
    ],
    "orders.paid"                       => [
        "EN" => "Order {0} has paid",
        "VI" => "Đơn hàng {0} đã thanh toán",
    ],
    "orders.enterprises.assigned"       => [
        "EN" => "Order #{0} has assigned to Enterprises",
        "VI" => "Đơn hàng #{0} đã được gán cho các đối tác",
    ],

    # Wallet
    "wallets.create-success"            => [
        "EN" => "You have just loaded {0} money successfully",
        "VI" => "Bạn vừa nạp {0} đồng thành công",
    ],

    # LatLong
    "lat_long.update-success"           => [
        "EN" => "Update location {0} successfully",
        "VI" => "Cập nhật ví trí {0} thành công",
    ],
    # Order History
    "order_histories.update-success"    => [
        "EN" => "Update order histories successfully",
        "VI" => "Bạn vừa cập nhật trạng thái đơn hàng thành công.",
    ],
    # Order status History
    "order_status_histories.update-success"    => [
        "EN" => "Update order histories successfully",
        "VI" => "Bạn vừa cập nhật trạng thái đơn hàng thành công.",
    ],
    "order_status_histories.update-failed"    => [
        "EN" => "Order status was in COMPLETED",
        "VI" => "Đơn hàng đã ở trạng thái COMPLETED.",
    ],
    "order_status_histories.canceled"    => [
        "EN" => "Order has been canceled",
        "VI" => "Đơn hàng đã hủy.",
    ],
    # User Status Orders
    "user_status_orders.update-success" => [
        "EN" => "Update order histories successfully",
        "VI" => "Bạn vừa cập nhật trạng thái đơn hàng thành công.",
    ],

    # Notify
    "notifies.fk"                       => [
        "EN" => "Can not delete [{0}] because using in info",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "notifies.create-success"           => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "notifies.update-success"           => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "notifies.delete-success"           => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Membership Rank
    "membership_ranks.fk"               => [
        "EN" => "Can not delete [{0}] because using in info",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "membership_ranks.create-success"   => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "membership_ranks.update-success"   => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "membership_ranks.delete-success"   => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    "promotions.fk"                    => [
        "EN" => "Can not delete [{0}] because using in promotions",
        "VI" => "[{0}] không thể xóa do đang sử dụng trong danh sách khuyến mãi",
    ],
    "promotions.not-exist"             => [
        "EN" => "Promotion #{0} not exist",
        "VI" => "Khuyến mãi #{0} không tồn tại",
    ],
    "promotions.existed"               => [
        "EN" => "Promotion #{0} does exist",
        "VI" => "Khuyến mãi #{0} đã tồn tại",
    ],
    "promotions.create-success"        => [
        "EN" => "Promotion #{0} has created successful",
        "VI" => "Khuyến mãi #{0} vừa được tạo thành công",
    ],
    "promotions.update-success"        => [
        "EN" => "Promotion #{0} has updated successful",
        "VI" => "Khuyến mãi #{0} vừa được chỉnh sửa thành công",
    ],
    "promotions.delete-success"        => [
        "EN" => "Promotion #{0} has deleted",
        "VI" => "Bạn vừa xóa khuyến mãi #{0} thành công",
    ],
    "promotions.change-status-success" => [
        "EN" => "Promotion #{0} has register successful",
        "VI" => "Khuyến mãi #{0} đã được thay đổi trạng thái thành công",
    ],
    "promotions.active-success"        => [
        "EN" => "Promotion #{0} has activated successful",
        "VI" => "Khuyến mãi #{0} vừa được kích hoạt thành công",
    ],
    "promotions.inactive-success"      => [
        "EN" => "Promotion #{0} has inactivated successful",
        "VI" => "Khuyến mãi #{0} vừa được vô hiệu hóa thành công",
    ],
    //config shipping
    "config-shipping.active-success"        => [
        "EN" => "Config shipping #{0} has activated successful",
        "VI" => "Cấu hình #{0} vừa được kích hoạt thành công",
    ],
    "config-shipping.inactive-success"      => [
        "EN" => "Config shipping #{0} has inactivated successful",
        "VI" => "Cấu hình #{0} vừa được vô hiệu hóa thành công",
    ],
    "config-shipping.delete-success"        => [
        "EN" => "Config shipping #{0} has deleted",
        "VI" => "Bạn vừa xóa cấu hình #{0} thành công",
    ],
    //Product user
    "product-users.create-success"     => [
        "EN" => "Assign a counterpart to a successful partne",
        "VI" => "Gán sản phản cho đối tác thành công",
    ],
    "product-users.update-success"     => [
        "EN" => "Partner's product has been edited successfully",
        "VI" => "Sản phẩm của đối tác đã được chỉnh sửa thành công",
    ],
    "product-users.delete-success"     => [
        "EN" => "You have successfully deleted a partner's product",
        "VI" => "Bạn vừa xóa sản phẩm của đối tác thành công",
    ],

    //Banner
    "banners.fk"                       => [
        "EN" => "Can not delete [{0}] because using in plant bug",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "banners.create-success"           => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "banners.update-success"           => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "banners.delete-success"           => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # App Menu
    "app-menus.fk"                     => [
        "EN" => "Can not delete [{0}] because using in plant bug",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "app-menus.create-success"         => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "app-menus.update-success"         => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "app-menus.delete-success"         => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Features
    "features"                         => [
        "EN" => "Feature",
        "VI" => "Tính năng",
    ],

    # Stores
    "stores"                           => [
        "EN" => "Store",
        "VI" => "Cửa hàng",
    ],
    "stores.fk"                        => [
        "EN" => "Can not delete [{0}] because using in roles",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "stores.create-success"            => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "stores.update-success"            => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "stores.delete-success"            => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Card
    "contact_support.fk"               => [
        "EN" => "Can not delete [{0}] because using",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "contact_support.create-success"   => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "contact_support.update-success"   => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "contact_support.delete-success"   => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    "wallets" => [
        "EN" => "Wallet",
        "VI" => "Ví",
    ],

    "wallets.not-enough"                => [
        "EN" => "Your balance is not enough to pay",
        "VI" => "Số tiền trong ví không đủ để thanh toán",
    ],
    "city.update-succes"           => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    //Table Name
    "cards"                             => [
        "EN" => "cards",
        "VI" => "thẻ",
    ],
    "cities"                            => [
        "EN" => "cities",
        "VI" => "tỉnh/thành phố",
    ],

    "countries"                         => [
        "EN" => "countries",
        "VI" => "quốc gia",
    ],
    "districts"                         => [
        "EN" => "districts",
        "VI" => "quận/huyện",
    ],
    "files"                             => [
        "EN" => "files",
        "VI" => "tệp tin",
    ],
    "folders"                           => [
        "EN" => "folders",
        "VI" => "thư mục",
    ],
    "images"                            => [
        "EN" => "images",
        "VI" => "hình ảnh",
    ],
    "notification_histories"            => [
        "EN" => "notification_histories",
        "VI" => "lịch sử thông báo",
    ],
    "notifies"                          => [
        "EN" => "notifies",
        "VI" => "thông báo",
    ],
    "permissions"                       => [
        "EN" => "permissions",
        "VI" => "quyền",
    ],
    "profiles"                          => [
        "EN" => "profiles",
        "VI" => "thông tin tài khoản",
    ],
    "profiles.gender.M"                 => [
        "EN" => "Male",
        "VI" => "Nam",
    ],
    "profiles.gender.F"                 => [
        "EN" => "Female",
        "VI" => "Nữ",
    ],
    "profiles.gender.O"                 => [
        "EN" => "Other",
        "VI" => "Khác",
    ],
    "role"                              => [
        "EN" => "role",
        "VI" => "vai trò",
    ],
    "settings"                          => [
        "EN" => "settings",
        "VI" => "cài đặt",
    ],
    "transaction_histories"             => [
        "EN" => "transaction_histories",
        "VI" => "lịch sử giao dịch",
    ],
    "stories"                           => [
        "EN" => "stories",
        "VI" => "cửa hàng",
    ],
    "user_locations"                    => [
        "EN" => "user_locations",
        "VI" => "vị trí người dùng",
    ],
    "user_logs"                         => [
        "EN" => "user_logs",
        "VI" => "lịch sử thao tác",
    ],
    "user_status_orders"                => [
        "EN" => "user_status_orders",
        "VI" => "lịch sử nhận/hủy đơn hàng",
    ],
    "wards"                             => [
        "EN" => "wards",
        "VI" => "phường/xã",
    ],
    "banners"                           => [
        "EN" => "banners",
        "VI" => "ảnh bìa/hình quảng cáo",
    ],
    "app_menus"                         => [
        "EN" => "app_menus",
        "VI" => "danh sách ứng dụng",
    ],
    "contact_supports"                  => [
        "EN" => "contact_supports",
        "VI" => "liên hệ hỗ trợ",
    ],
    "payment_histories"                 => [
        "EN" => "payment_histories",
        "VI" => "lịch sử giao dịch",
    ],
    "payment_vptransaction"             => [
        "EN" => "virtual account transaction Id",
        "VI" => "Id giao dịch virtual account",
    ],
    "payment_successful_refund"             => [
        "EN" => "Order {0} successful refund ",
        "VI" => "Đơn hàng {0} hoàn tiền thành công",
    ],
    "payment_failed_refund"             => [
        "EN" => "Order {0} failed refund ",
        "VI" => "Đơn hàng {0} hoàn tiền không thành công",
    ],
    "promotions"                        => [
        "EN" => "promotions",
        "VI" => "khuyến mãi",
    ],
    "search_histories"                  => [
        "EN" => "search_histories",
        "VI" => "lịch sử tìm kiếm",
    ],
    "wallet_histories"                  => [
        "EN" => "wallet_histories",
        "VI" => "lịch sử ví",
    ],
    "is_default"                        => [
        "EN" => "is_default",
        "VI" => "Đặt là mặc định",
    ],

    # Cart
    "carts.fk"                          => [
        "EN" => "Can not delete Cart because using in plant bug",
        "VI" => "Giỏ hàng không thể xóa do đang sử dụng",
    ],
    "carts.not-exist"                   => [
        "EN" => "Cart #{0} not exist",
        "VI" => "Giỏ hàng #{0} không tồn tại",
    ],
    "carts.create-success"              => [
        "EN" => "Cart has created successful",
        "VI" => "Sản phẩm đã được thêm vào giỏ hàng thành công",
    ],
    "carts.update-success"              => [
        "EN" => "Cart has updated successful",
        "VI" => "Giỏ hàng vừa được cập nhật thành công",
    ],
    "carts.delete-success"              => [
        "EN" => "Cart has deleted",
        "VI" => "Bạn vừa xóa giỏ hàng thành công",
    ],
    "carts.remove-item-success"         => [
        "EN" => "Item has deleted",
        "VI" => "Bạn vừa xóa sản phẩm trong giỏ hàng thành công",
    ],
    "carts.set-payment-method-success"  => [
        "EN" => "Set payment method success",
        "VI" => "Thêm phương thức thanh toán vào giỏ hàng thành công",
    ],
    "carts.set-shipping-method-success" => [
        "EN" => "Set shipping method success",
        "VI" => "Thêm phương thức giao hàng vào giỏ hàng thành công",
    ],

    # Cart Detail
    "cart_details"                      => [
        "EN" => "Detail in Cart",
        "VI" => "Sản phẩm trong giỏ",
    ],


    # Coupon
    "coupons.fk"                        => [
        "EN" => "Can not delete [{0}] because using in plant bug",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "coupons.create-success"            => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "coupons.update-success"            => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "coupons.delete-success"            => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    #Zalo
    "zalo.sync-product-success"         => [
        "EN" => "{0} Product(s) synchronized with Zalo successfully",
        "VI" => "{0} Sản phẩm đã đồng bộ với Zalo thành công",
    ],
    "zalo.sync-order-success"           => [
        "EN" => "Orders synchronized with Zalo successfully",
        "VI" => "Đơn hàng đã đồng bộ với Zalo thành công",
    ],
    "zalo.sync-update-order-success"    => [
        "EN" => "Updated order synchronized with Zalo successfully",
        "VI" => "Cập nhật đơn hàng đã đồng bộ với Zalo thành công",
    ],
    "zalo.sync-update-order-fail"       => [
        "EN" => "Updated order synchronized with Zalo fail",
        "VI" => "Cập nhật đơn hàng đã đồng bộ với Zalo thất bại",
    ],

    "warehouses.create-success" => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],

    "warehouses.update-success" => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],

    "warehouses.delete-success"                 => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],


    # Order Status
    "order_status.fk"                           => [
        "EN" => "Can not delete [{0}] because using in info",
        "VI" => "[{0}] không thể xóa do đang sử dụng",
    ],
    "order_status.create-success"               => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "order_status.update-success"               => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "order_status.delete-success"               => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Batch
    "batch.create-success"                      => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "batch.update-success"              => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "batch.delete-success"              => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Street Address
    "street_address.create-success"     => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "street_address.update-success"     => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "street_address.delete-success"     => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],
    "street_address.set-is-default"     => [
        "EN" => "[{0}] set is default successful",
        "VI" => "[{0}] vừa được đặt là mặc định.",
    ],
    "street_address.set-street-address" => [
        "EN" => "Set shipping address successful",
        "VI" => "Thêm địa chỉ giạo hàng thành công.",
    ],

    #Invoices
    "invoices.create-success"           => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "invoices.update-success"           => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "invoices.delete-success"           => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    #Product Comment\
    "product_comments"                  => [
        "EN" => "Product comment",
        "VI" => "Đánh giá",
    ],
    "product_comments.create-success"   => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "product_comments.update-success"   => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "product_comments.delete-success"   => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Blog
    "blogs.fk"                          => [
        "EN" => "Can not delete Blog because using in plant bug",
        "VI" => "Blog không thể xóa do đang sử dụng",
    ],
    "blogs.not-exist"                   => [
        "EN" => "Blog #{0} not exist",
        "VI" => "Blog #{0} không tồn tại",
    ],
    "blogs.create-success"              => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "blogs.update-success"              => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được sửa thành công",
    ],
    "blogs.delete-success"              => [
        "EN" => "[{0}] has deleted",
        "VI" => "[{0}] vừa xóa thành công",
    ],
    #Blog Category
    "blog_categories.create-success"    => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "blog_categories.update-success"    => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "blog_categories.delete-success"    => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],
    #Post
    "posts.create-success"              => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "posts.update-success"              => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "posts.approve-success"             => [
        "EN" => "[{0}] has approved successful",
        "VI" => "[{0}] vừa được phê duyệt thành công",
    ],
    "posts.delete-success"              => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Post Comment
    "post_comments.create-success"              => [
        "EN" => "Comment [{0}] has created successful",
        "VI" => "Bình luận cho [{0}] thành công",
    ],
    "post_comments.update-success"              => [
        "EN" => "[{0}] has updated successful",
        "VI" => "Bình luận [{0}] cho [{1}] vừa được chỉnh sửa thành công",
    ],
    "post_comments.delete-success"              => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],
    #Attribute Groups
    "attribute_groups.create-success"              => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "attribute_groups.update-success"              => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "attribute_groups.approve-success"             => [
        "EN" => "[{0}] has approved successful",
        "VI" => "[{0}] vừa được phê duyệt thành công",
    ],
    "attribute_groups.delete-success"              => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    #Attributes
    "attributes.create-success"              => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "attributes.update-success"              => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "attributes.approve-success"             => [
        "EN" => "[{0}] has approved successful",
        "VI" => "[{0}] vừa được phê duyệt thành công",
    ],
    "attributes.delete-success"              => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Product attributes
    "product_attributes.update-success"              => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được cập nhập thành công",
    ],

    # Product variant
    "product_variants.update-success"              => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được cập nhập thành công",
    ],
    "product_variants.delete-success"              => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],
    "product_variants.promotion.delete-success"              => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    # Shipping
    "shipping_adress.create-success"            => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ], "shipping_address.not-exist"            => [
        "EN" => "[{0}] does not exist",
        "VI" => "[{0}] Không tồn tại",
    ],
    "shipping_adress.update-success"            => [
        "EN" => "[{0}] has update successful",
        "VI" => "[{0}] vừa được cập nhật thành công",
    ],
    # Poll
    "poll.create-success" => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "poll.update-success" => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được cập nhật thành công",
    ],
    "poll.delete-success" => [
        "EN" => "[{0}] has deleted successful",
        "VI" => "[{0}] vừa được xóa thành công",
    ],
    "poll.perform-success" => [
        "EN" => "[{0}] has performed successful",
        "VI" => "[{0}] vừa được thực hiện thành công",
    ],
    # Website
    "websites.create-success"                   => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "websites.update-success"                   => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được cập nhật thành công",
    ],
    "websites.delete-success"                   => [
        "EN" => "[{0}] has deleted successful",
        "VI" => "[{0}] vừa được xóa thành công",
    ],

    # Price
    "prices.create-success" => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "prices.update-success" => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được cập nhật thành công",
    ],
    "prices.delete-success" => [
        "EN" => "[{0}] has deleted successful",
        "VI" => "[{0}] vừa được xóa thành công",
    ],

    # Sale_prices
    "sale_prices.create-success" => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "sale_prices.update-success" => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được cập nhật thành công",
    ],
    "sale_prices.delete-success" => [
        "EN" => "[{0}] has deleted successful",
        "VI" => "[{0}] vừa được xóa thành công",
    ],

    # Call History
    "callhistories.vote-success"           => [
        "EN" => "Vote success. Thank you!",
        "VI" => "Đánh giá [{0}] thành công. Cảm ơn bạn!",
    ],

    #Brands
    "brands.create-success"              => [
        "EN" => "[{0}] has created successful",
        "VI" => "[{0}] vừa được tạo thành công",
    ],
    "brands.update-success"              => [
        "EN" => "[{0}] has updated successful",
        "VI" => "[{0}] vừa được chỉnh sửa thành công",
    ],
    "brands.approve-success"             => [
        "EN" => "[{0}] has approved successful",
        "VI" => "[{0}] vừa được phê duyệt thành công",
    ],
    "brands.delete-success"              => [
        "EN" => "[{0}] has deleted",
        "VI" => "Bạn vừa xóa [{0}] thành công",
    ],

    #Coupon
    "coupon.update-error"              => [
        "EN" => "Failed to add coupon code",
        "VI" => "Thêm mã giảm giá thất bại.",
    ],
    "coupon.update-success"              => [
        "EN" => "success to add coupon code",
        "VI" => "Thêm mã giảm giá thành công.",
    ],

    #Reason
    "reason.not-exist"              => [
        "EN" => "Reason not exist",
        "VI" => "Bạn chưa nhập lý do.",
    ],


    # File
    "file.upload-success"             => [
        "EN" => "[{0}] has upload successful",
        "VI" => "[{0}] vừa tải lên thành công",
    ],

    "cdp_logs_orders.update-success"             => [
        "EN" => "Log CDP #{0} has updated successful",
        "VI" => "Đơn hàng #{0} vừa được thao tác gửi lại thành công",
    ],
    "cdp_logs_orders.update-success"             => [
        "EN" => "Log CDP #{0} has updated successful",
        "VI" => "Đơn hàng #{0} vừa được thao tác gửi lại thành công",
    ],

];
