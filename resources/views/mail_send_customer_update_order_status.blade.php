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
            width: 100%;
            margin: 0 auto;
            padding: 0;
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
            -premailer-width: 100%;
            -premailer-cellpadding: 0;
            -premailer-cellspacing: 0;
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
        <td align="center">
            <table class="email-content" width="100%" cellpadding="0" cellspacing="0">
{{--                <tr>--}}
{{--                    <td class="email-masthead">--}}
{{--                        <a href="{{env('APP_URL')}}" class="email-masthead_name">--}}
{{--                            <img style="max-width: 100px;" src="{{$logo ?? env('APP_LOGO')}}"--}}
{{--                                 alt="{{env('APP_NAME')}}"/>--}}
{{--                        </a>--}}
{{--                    </td>--}}
{{--                </tr>--}}
                <!-- Email Body -->
                <tr>
                    <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                        <table class="email-body_inner" align="center" cellpadding="0" cellspacing="0">
                            <!-- Body content -->
                            <tr>
                                <td class="content-cell">
                                    <h1>Xin chào {{$order->customer->name ?? null}},</h1>
                                    <p> Đơn hàng: <strong>#{{$order->code}}</strong> vừa được thay đổi trạng thành
                                        <strong>{{ORDER_STATUS_NAME[$order->status] ?? $order->status}}</strong></p>
                                    <!-- Action -->
                                    <p>&nbsp;</p>
                                    <p>Vui lòng theo dõi email để biết tình trạng của đơn hàng nhé</p>
                                    <br/>
                                    <p>Chi tiết đơn hàng</p>
                                    Mã đơn hàng: <strong>{{$order->code}}</strong> <br/>
                                    Cửa hàng: <strong>{{$order->store->name ?? null}}</strong> <br/>
                                    Người giao: <strong>{{$order->partner->name ?? null}}</strong> <br/>
                                    <p>Chi tiết đơn hàng</p>
                                    <table class="body-action" align="center" width="100%" cellpadding="0"
                                           cellspacing="0">
                                        <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>Đơn giá</th>
                                            <th>Tổng tiền</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                            $total = 0;
                                            $totalTmp = 0;
                                            $totalPriceDown = 0;
                                        @endphp
                                        @foreach($order->details as $detail)
                                            @php
                                                $total += (($detail->price_down > 0 ? $detail->price_down : $detail->price) * $detail->qty);
                                                $totalTmp += ($detail->qty * $detail->price);
                                                $totalPriceDown += ($detail->qty * ($detail->price_down > 0 ? $detail->price - $detail->price_down : 0));
                                            @endphp
                                            <tr>
                                                <td style="text-align: left;">
                                                    {{($detail->product->name ?? $detail->product_id). " - " .($detail->product->unit->name ?? null)}}
                                                    <br/>
                                                    <strong>
                                                        {{ $detail->product->code ?? $detail->product_id }}<br/>
                                                        Số lượng: {{number_format($detail->qty)}}
                                                    </strong>
                                                </td>
                                                @if($detail->price_down > 0)
                                                    <td style="min-width: 80px;">
                                                        <del>{{number_format($detail->price)}}</del>
                                                        <br/>
                                                        <span style="color: #0000ff">{{number_format($detail->price_down)}}</span>
                                                    </td>
                                                    <td style="color: #ff0000; min-width: 80px;">
                                                        <del>{{number_format($detail->price * $detail->qty)}}</del>
                                                        <br/>
                                                        <span style="color: #0000ff">{{number_format($detail->price_down * $detail->qty)}}</span>
                                                    </td>
                                                @else
                                                    <td style="min-width: 80px;">
                                                        {{number_format($detail->price)}}
                                                    </td>
                                                    <td style="color: #ff0000; min-width: 80px;">
                                                        <strong>{{number_format($detail->total)}}</strong>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="2" style="text-align: right;">Tạm tính</td>
                                            <td>{{number_format($totalTmp)}}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align: right;">Chiết khấu</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align: right;">Giảm giá</td>
                                            <td>{{number_format($totalPriceDown)}}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="text-align: right;">Thành tiền</td>
                                            <td style="color: red;">{{number_format($total)}}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
{{--                <tr>--}}
{{--                    <td>--}}
{{--                        <table class="email-footer" align="center" width="100%" cellpadding="0" cellspacing="0">--}}
{{--                            <tr>--}}
{{--                                <td class="content-cell" align="center">--}}
{{--                                    <p class="sub align-center">&copy; 2020 TM Solution Co, Ltd,. All rights--}}
{{--                                        reserved.</p>--}}
{{--                                    <p class="sub align-center">--}}
{{--                                        Vincom Center, 70-72 Le Thanh Ton Street, Dist 1, HCMC.--}}
{{--                                        <br>Phone: 0933 567 221 - Fax 0933 567 221--}}
{{--                                        <br>Email: <a href="mailto:support@kpis.com.vn">support@kpis.com.vn</a>--}}
{{--                                    </p>--}}
{{--                                </td>--}}
{{--                            </tr>--}}
{{--                        </table>--}}
{{--                    </td>--}}
{{--                </tr>--}}
            </table>
        </td>
    </tr>
</table>
</body>
</html>