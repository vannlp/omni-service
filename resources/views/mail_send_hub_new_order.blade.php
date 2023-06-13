<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>Error</title>

    <style type="text/css" rel="stylesheet" media="all">
        /* Base ------------------------------ */

        *:not(br):not(tr):not(html) {
            font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;
            box-sizing: border-box;
        }

        body {
            width: 100% !important;
            height: 100%;
            margin: 0;
            line-height: 1.4;
            background-color: #F2F4F6;
            color: #74787E;
            -webkit-text-size-adjust: none;
        }

        p,
        ul,
        ol,
        blockquote {
            line-height: 1.4;
            text-align: left;
        }

        a {
            color: #3869D4;
        }

        a img {
            border: none;
        }

        td {
            word-break: break-word;
        }

        /* Layout ------------------------------ */

        .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #F2F4F6;
        }

        .email-content {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }

        /* Masthead ----------------------- */

        .email-masthead {
            padding: 25px 0 0;
            text-align: center;
        }

        .email-masthead_logo {
            width: 94px;
        }

        .email-masthead_name {
            font-size: 60px;
            font-weight: bold;
            color: #333;
            text-decoration: none;
            text-shadow: 0 1px 0 white;
        }

        /* Body ------------------------------ */

        .email-body {
            width: 100%;
            margin: 0;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            border-top: 1px solid #EDEFF2;
            border-bottom: 1px solid #EDEFF2;
            background-color: #FFFFFF;
        }

        .email-body_inner {
            width: 80%;
            margin: 0 auto;
            padding: 0;
            margin-left: 30px;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #FFFFFF;
        }

        .email-footer {
            width: 100%;
            margin: 0 auto;
            padding: 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            text-align: center;
        }

        .email-footer p {
            color: #AEAEAE;
        }

        .body-action {
            width: 100%;
            margin: 30px auto;
            padding: 0;
            /*-premailer-width: 100%;*/
            /*-premailer-cellpadding: 0;*/
            /*-premailer-cellspacing: 0;*/
            text-align: center;
        }

        .body-action tr td {
            border: 1px solid #ccc;
        }

        .body-sub {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #EDEFF2;
        }

        .content-cell {
            padding: 0px;
        }

        .preheader {
            display: none !important;
            visibility: hidden;
            mso-hide: all;
            font-size: 1px;
            line-height: 1px;
            max-height: 0;
            max-width: 0;
            opacity: 0;
            overflow: hidden;
        }

        /* Attribute list ------------------------------ */

        .attributes {
            margin: 0 0 21px;
        }

        .attributes_content {
            background-color: #EDEFF2;
            padding: 16px;
        }

        .attributes_item {
            padding: 0;
        }

        /* Related Items ------------------------------ */

        .related {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }

        .related_item {
            padding: 10px 0;
            color: #74787E;
            font-size: 15px;
            line-height: 18px;
        }

        .related_item-title {
            display: block;
            margin: .5em 0 0;
        }

        .related_item-thumb {
            display: block;
            padding-bottom: 10px;
        }

        .related_heading {
            border-top: 1px solid #EDEFF2;
            text-align: center;
            padding: 25px 0 10px;
        }

        /* Discount Code ------------------------------ */

        .discount {
            width: 100%;
            margin: 0;
            padding: 24px;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
            background-color: #EDEFF2;
            border: 2px dashed #9BA2AB;
        }

        .discount_heading {
            text-align: center;
        }

        .discount_body {
            text-align: center;
            font-size: 15px;
        }

        /* Social Icons ------------------------------ */

        .social {
            width: auto;
        }

        .social td {
            padding: 0;
            width: auto;
        }

        .social_icon {
            height: 20px;
            margin: 0 8px 10px 8px;
            padding: 0;
        }

        /* Data table ------------------------------ */

        .purchase {
            width: 100%;
            margin: 0;
            padding: 35px 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }

        .purchase_content {
            width: 100%;
            margin: 0;
            padding: 25px 0 0 0;
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
        }

        .purchase_item {
            padding: 10px 0;
            color: #74787E;
            font-size: 15px;
            line-height: 18px;
        }

        .purchase_heading {
            padding-bottom: 8px;
            border-bottom: 1px solid #EDEFF2;
        }

        .purchase_heading p {
            margin: 0;
            color: #9BA2AB;
            font-size: 12px;
        }

        .purchase_footer {
            padding-top: 15px;
            border-top: 1px solid #EDEFF2;
        }

        .purchase_total {
            margin: 0;
            text-align: right;
            font-weight: bold;
            color: #2F3133;
        }

        .purchase_total--label {
            padding: 0 15px 0 0;
        }

        /* Utilities ------------------------------ */

        .align-right {
            text-align: right;
        }

        .align-left {
            text-align: left;
        }

        .align-center {
            text-align: center;
        }

        /*Media Queries ------------------------------ */

        @media only screen and (max-width: 600px) {
            .email-body_inner,
            .email-footer {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }

        /* Buttons ------------------------------ */

        .button {
            background-color: #3869D4;
            border-top: 10px solid #3869D4;
            border-right: 18px solid #3869D4;
            border-bottom: 10px solid #3869D4;
            border-left: 18px solid #3869D4;
            display: inline-block;
            color: #FFF;
            text-decoration: none;
            border-radius: 3px;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
            -webkit-text-size-adjust: none;
        }

        .button--green {
            background-color: #22BC66;
            border-top: 10px solid #22BC66;
            border-right: 18px solid #22BC66;
            border-bottom: 10px solid #22BC66;
            border-left: 18px solid #22BC66;
        }

        .button--red {
            background-color: #FF6136;
            border-top: 10px solid #FF6136;
            border-right: 18px solid #FF6136;
            border-bottom: 10px solid #FF6136;
            border-left: 18px solid #FF6136;
        }

        /* Type ------------------------------ */

        h1 {
            margin-top: 0;
            color: #2F3133;
            font-size: 19px;
            font-weight: bold;
            text-align: left;
        }

        h2 {
            margin-top: 0;
            color: #2F3133;
            font-size: 16px;
            font-weight: bold;
            text-align: left;
        }

        h3 {
            margin-top: 0;
            color: #2F3133;
            font-size: 14px;
            font-weight: bold;
            text-align: left;
        }

        p {
            margin-top: 0;
            color: #74787E;
            font-size: 16px;
            line-height: 1.5em;
            text-align: left;
        }

        p.sub {
            font-size: 12px;
        }

        p.center {
            text-align: center;
        }

    </style>
</head>
<body>
<table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="left">
            <table class="email-content" width="100%" cellpadding="0" cellspacing="0">
            <!-- Email Body -->
                <tr>
                    <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                        <table class="email-body_inner" align="left" cellpadding="0" cellspacing="0">
                            <!-- Body content -->
                            <tr>
                                <td class="content-cell">
                                    <h1>Kính gửi Quý Nhà Phân Phối {{$order->distributor->name ?? $order->distributor_name}},</h1>
                                    <p>Quý NPP vừa nhận được đơn hàng TMĐT từ NutifoodShop.com: <strong><a href="https://admin.nutifoodshop.com/#/orders/{{$order->id}}/edit">#{{$order->code}}</a></strong></p>
                                    <p>Vui lòng bấm vào mã đơn để xử lý đơn hàng.</p>
                                    <!-- Action -->
                                    <p>Thông tin chi tiết đơn hàng:</p>
                                    <p>Khách hàng: <strong>{{$order->customer->name ?? null}}</strong></p>
                                    <p>Mã đơn hàng: <strong><a href="https://admin.nutifoodshop.com/#/orders/{{$order->id}}/edit">{{$order->code}}</a></strong></p>
                                    <p>Dịch vụ giao hàng: <strong>{{LADING_METHOD[$order->lading_method]}}</strong></p>
                                    <p style="color:#ff0000">Tổng thời gian ĐH chưa được xử lý: <strong>{{$order->number_h ?? null}} giờ</strong></p>
                                    <p style="color:#ff0000">Tổng số ngày ĐH chưa được xử lý: <strong>{{$order->number_day ?? null}} ngày</strong></p>
                                    <p>Chi tiết đơn hàng:</p>
                                    <table class="body-action" align="center" width="100%" cellpadding="0"
                                           cellspacing="0">
                                        <thead style="background-color: #5fbafe">
                                        <tr style="height: 50px">
                                            <th>Sản phẩm</th>
                                            <th>Số lượng (thùng)</th>
                                            <th>Đơn giá</th>
                                            <th>Tổng tiền</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($order->details as $detail)
                                            <tr>
                                                <td style="text-align: left; width: 450px">
                                                    <strong>
                                                        &nbsp;{{ $detail->product->code ?? $detail->product_id }} -
                                                    </strong>
                                                    {{($detail->product->name ?? $detail->product_id)}}
                                                </td>
                                                <td style="min-width: 0px;width: 8%">
                                                    {{number_format($detail->qty)}}
                                                </td>
                                                <td style="min-width: 0px; width: 15%; text-align: right;">
                                                    {{number_format($detail->price)}}đ&nbsp;
                                                </td>
                                                <td style="color: #ff0000; min-width: 0px; width: 15%; text-align: right;">
                                                    <strong>{{number_format($detail->total)}}đ&nbsp;</strong>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="3" style="text-align: right;">Tạm tính&nbsp;</td>
                                            <td style="text-align: right">{{number_format($order->sub_total_price)}}đ&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" style="text-align: right;">Chiết khấu&nbsp;</td>
                                            <td style="text-align: right">{{number_format($order->total_discount)}}đ&nbsp;</td>
                                        </tr>
                                        @if(!$order->promotionTotals->isEmpty())
                                            @foreach($order->promotionTotals as $item)
                                                <tr>
                                                    <td colspan="3"
                                                        style="text-align: right;">{{ $item->promotion_name }}</td>
                                                    <td style="text-align: right">{{number_format($item->value)}}đ&nbsp;</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                        <tr>
                                            <td colspan="3" style="text-align: right;">Thành tiền&nbsp;</td>
                                            <td style="color: red;text-align: right">{{number_format($order->total_price)}}đ&nbsp;</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <p>Quý NPP vui lòng <a href="https://admin.nutifoodshop.com/#/orders/{{$order->id}}/edit">bấm vào đây</a> để truy cập hệ thống xử lý đơn hàng nhanh chóng.</p>
                                    <p style="color: red"><b>Lưu ý: NPP vui lòng xuất date mới từ 70% cho người tiêu dùng cuối để tránh phát sinh khiếu nại và đổi trả.</b></p>
                                    <p>Xin cảm ơn Quý NPP đã hỗ trợ.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>