<!DOCTYPE html>
<html lang="en">
<head>
    <title>PAYMENT RECEIPT</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    </style>
</head>
<body>
<table style="font-size:8px">
    <br/>
    <tr>
        <td>
            <p><strong>Khách hàng: </strong>{{object_get($data['order'], 'customer.profile.full_name')}}<br/>
{{--                <strong>Mã số thuế: </strong>{{$data['tax_code']}}<br/>--}}
                <strong>Số điện thoại: </strong>{{object_get($data['order'], 'shipping_address_phone')}}<br/>
                <strong>Địa chỉ giao
                        hàng: </strong>{{!empty($data['streetCustomer']) ? $data['addressCustomer'] : $data['address']}}
                <br/>
                @if(!empty($data['streetCustomer']))
                    {{$data['streetCustomer']}}<br/>
                    <strong>Ghi chú: </strong>{{object_get($data['order'], 'note')}}
                @else
                    <strong>Ghi chú: </strong>{{object_get($data['order'], 'note')}}
                @endif
            </p>
        </td>
        <td>
            <p><strong>Trình trạng thanh
                       toán: {{!empty(object_get($data['order'], 'payment_status')) ? "Đã thanh toán" : "Chưa thanh toán"}}</strong><br/>
                <strong>Phương thức thanh
                        toán: </strong>{{$data['payment_method']}}
                <br/>
            </p>
        </td>
    </tr>
</table>

<h3>CHI TIẾT ĐƠN HÀNG</h3>
<table style="font-size:8px">
    <thead>
    <tr style="border: 1px solid #ccc;">
        <th style="border: 1px solid #ccc; background-color: #eee; width: 25px; text-align: center">
            &nbsp;<br/>STT<br/></th>
        <th style="border: 1px solid #ccc; background-color: #eee; width: 50px; text-align: center">&nbsp;<br/>Mã
                                                                                                    hàng<br/></th>
        <th style="border: 1px solid #ccc; background-color: #eee; width: 100px; text-align: center">&nbsp;<br/>Tên hàng
                                                                                                     hoá, dịch vụ<br/>
        </th>
        <th style="border: 1px solid #ccc; background-color: #eee; width: 60px; text-align: center">&nbsp;<br/>Đơn vị
                                                                                                    tính<br/></th>
        {{--        <th style="border: 1px solid #ccc; background-color: #eee; width: 70px; text-align: center">--}}
        {{--            &nbsp;<br/>Kho<br/></th>--}}
        <th style="border: 1px solid #ccc; background-color: #eee; width: 50px; text-align: center">&nbsp;<br/>Số
                                                                                                    lượng<br/></th>
        <th style="border: 1px solid #ccc; background-color: #eee; width: 75px; text-align: center">&nbsp;<br/>Đơn
                                                                                                    giá<br/></th>
        <th style="border: 1px solid #ccc; background-color: #eee; width: 35px; text-align: center">
            &nbsp;<br/>Thuế<br/></th>
        <th style="border: 1px solid #ccc; background-color: #eee; width: 60px; text-align: center">&nbsp;<br/>Chiết
                                                                                                    khấu<br/></th>
        <th style="border: 1px solid #ccc; background-color: #eee; width: 85px; text-align: center">&nbsp;<br/>Thành
                                                                                                    tiền<br/></th>
    </tr>
    </thead>
    <tbody>
    @foreach(object_get($data['order'], 'details', []) as $key=>$detail)
        <tr style="border: 1px solid #eee;">
            <td style="border: 1px solid #ccc; width: 25px; text-align: center">
                &nbsp;<br/>{{$key+1}}<br/></td>
            <td style="border: 1px solid #ccc; width: 50px; text-align: center">
                &nbsp;<br/>{{object_get($detail, 'product.code')}}</td>
            <td style="border: 1px solid #ccc; width: 100px;text-align: center">
                &nbsp;<br/>{{object_get($detail, 'product.name')}}</td>
            <td style="border: 1px solid #ccc; width: 60px; text-align: center">
                &nbsp;<br/>{{object_get($detail, 'product.getUnit.name')}}
            </td>
            {{--            <td style="border: 1px solid #ccc; width: 70px; ">--}}
            {{--                &nbsp;<br/>{{object_get($detail, 'warehouse.code')}}</td>--}}
            <td style="border: 1px solid #ccc; width: 50px; text-align: center;">
                &nbsp;<br/>{{empty($detail->qty) ? "_":number_format(object_get($detail, 'qty'), 0, ',', '.')}}</td>
            <td style="border: 1px solid #ccc; width: 75px; text-align: right;">
                &nbsp;<br/>{{empty(object_get($detail, 'price', 0)) ? "-": number_format(object_get($detail, 'price', 0), 0, ',', '.')}}
            </td>
            <td style="border: 1px solid #ccc; width: 35px; text-align: center;">
                &nbsp;<br/>{{empty(object_get($detail, 'product.tax', 0)) ? "-": number_format(object_get($detail, 'product.tax', 0), 0, ',', '.')}}
            </td>
            <td style="border: 1px solid #ccc; width: 60px; text-align: right;">
                &nbsp;<br/>{{empty(object_get($detail, 'discount', 0)) ? "-": number_format(object_get($detail, 'discount', 0), 0, ',', '.')}}
            </td>
            <td style="border: 1px solid #ccc; width: 85px; text-align: right;">
                @php
                    $totalPrice = object_get($detail, 'total', 0);
                    if(empty($totalPrice)){
                        $totalPrice = "-";
                    } else {
                        $totalPrice = number_format($totalPrice, 0, ',', '.');
                    }
                @endphp
                &nbsp;<br/>{{!empty(object_get($detail, 'decrement', 0))?"-":$totalPrice}}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<br/>
<br/>
{{--<table style="font-size:8px">--}}
{{--    <tbody>--}}
{{--    <tr style="border: 1px solid #ccc;">--}}
{{--        <th style="border: 1px solid #ccc; background-color: #eee; width: 90px; text-align: center">--}}
{{--            &nbsp;<br/><br/></th>--}}
{{--        <th style="border: 1px solid #ccc; background-color: #eee; width: 90px; text-align: center">&nbsp;<br/>Không--}}
{{--                                                                                                    chịu thuế<br/></th>--}}
{{--        <th style="border: 1px solid #ccc; background-color: #eee; width: 90px; text-align: center">&nbsp;<br/>Thuế suất--}}
{{--                                                                                                    0%<br/></th>--}}
{{--        <th style="border: 1px solid #ccc; background-color: #eee; width: 90px; text-align: center">&nbsp;<br/>Thuế suất--}}
{{--                                                                                                    5%<br/></th>--}}
{{--        <th style="border: 1px solid #ccc; background-color: #eee; width: 90px; text-align: center">&nbsp;<br/>Thuế suất--}}
{{--                                                                                                    10%<br/></th>--}}
{{--        <th style="border: 1px solid #ccc; background-color: #eee; width: 90px; text-align: center">&nbsp;<br/>Thành--}}
{{--                                                                                                    tiền<br/></th>--}}
{{--    </tr>--}}
{{--    </tbody>--}}
{{--    <tbody>--}}
{{--    <tr style="border: 1px solid #eee;">--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: center">--}}
{{--            &nbsp;<br/>Tiền hàng hóa, dịch vụ<br/></td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>{{empty($data['orderTax']) ? "-": number_format($data['orderTax'], 0, ',', '.')}}</td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>{{empty($data['orderTax0']) ? "-": number_format($data['orderTax0'], 0, ',', '.')}}</td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>{{empty($data['orderTax5']) ? "-": number_format($data['orderTax5'] / 1.05, 0, ',', '.')}}</td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>{{empty($data['orderTax10']) ? "-": number_format($data['orderTax10'] / 1.10, 0, ',', '.')}}</td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            @php--}}
{{--                $totalPrice = $data['orderTax'] + $data['orderTax0'] + $data['orderTax5'] / 1.05 + $data['orderTax10'] / 1.10;--}}
{{--            @endphp--}}
{{--            &nbsp;<br/>{{empty($totalPrice) ? "-": number_format($totalPrice, 0, ',', '.')}}--}}
{{--        </td>--}}
{{--    </tr>--}}
{{--    </tbody>--}}
{{--    <tbody>--}}
{{--    <tr style="border: 1px solid #eee;">--}}
{{--        <td style="border: 1px solid #ccc; width: 90px;  text-align: center">--}}
{{--            &nbsp;<br/>Tiền thuế<br/></td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>---}}
{{--        </td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>---}}
{{--        </td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            @php--}}
{{--                $tax5 = $data['orderTax5']  - $data['orderTax5'] / 1.05;--}}
{{--                $tax10 = $data['orderTax10'] - $data['orderTax10'] / 1.10;--}}
{{--                $tax510 = $data['orderTax5']  - $data['orderTax5'] / 1.05 + $data['orderTax10'] - $data['orderTax10'] / 1.10;--}}
{{--            @endphp--}}
{{--            &nbsp;<br/>{{empty($tax5) ? "-": number_format($tax5, 0, ',', '.')}}</td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>{{empty($tax10) ? "-": number_format($tax10, 0, ',', '.')}}</td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>{{empty($tax510) ? "-": number_format($tax510, 0, ',', '.')}}--}}
{{--        </td>--}}
{{--    </tr>--}}
{{--    </tbody>--}}
{{--    <tbody>--}}
{{--    <tr style="border: 1px solid #eee;">--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: center">--}}
{{--            &nbsp;<br/>Thanh toán<br/></td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>{{empty($data['orderTax']) ? "-": number_format($data['orderTax'], 0, ',', '.')}}</td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>{{empty($data['orderTax0']) ? "-": number_format($data['orderTax0'], 0, ',', '.')}}</td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>{{empty($data['orderTax5']) ? "-": number_format($data['orderTax5'], 0, ',', '.')}}</td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>{{empty($data['orderTax10']) ? "-": number_format($data['orderTax10'], 0, ',', '.')}}</td>--}}
{{--        <td style="border: 1px solid #ccc; width: 90px; text-align: right;">--}}
{{--            &nbsp;<br/>{{empty($data['total']) ? "-": number_format($data['total'] - object_get($data['order'], 'discount',0), 0, ',', '.')}}</td>--}}
{{--    </tr>--}}
{{--    </tbody>--}}
{{--    <tbody>--}}
{{--    <tr style="border: 1px solid #eee;">--}}
{{--        <td style="border: 1px solid #ccc; width: 540px;">--}}
{{--            @php--}}
{{--                $taxTotal = (($data['orderTax'] + $data['orderTax0'] + $data['orderTax5'] + $data['orderTax10']) - $data['total']);--}}
{{--            @endphp--}}
{{--            <span style="position: absolute;"><strong>Số tiền chiết--}}
{{--                    khấu:</strong> {{number_format($taxTotal + object_get($data['order'], 'discount',0))}}--}}
{{--                đ.--}}
{{--            </span></td>--}}
{{--    </tr>--}}
{{--    </tbody>--}}
{{--</table>--}}
<table style="font-size:8px">
    <tbody>
    <tr style="border: 1px solid #eee;">
        <td style="border: 1px solid #ccc; width: 540px;">
            <span style="margin-left: 10px"><strong>Tạm tính:</strong> {{number_format($data['tempPrice'] - object_get($data['order'], 'discount',0)) . " đ"}}</span>
        </td>
    </tr>
    @if(!empty(object_get($data['order'], 'coupon_code')))
        <tr style="border: 1px solid #eee;">
            <td style="border: 1px solid #ccc; width: 260px;">
                <span style="margin-left: 10px"><strong>Phiếu mua hàng:</strong> {{object_get($data['order'], 'coupon_code')}}</span>
            </td>
            <td style="border: 1px solid #ccc; width: 280px;">
                <span style="margin-left: 10px"><strong>Giá giảm:</strong> {{number_format(object_get($data['order'], 'total_discount',0)) . " đ"}}</span>
            </td>
        </tr>
    @endif
    <tr style="border: 1px solid #eee;">
        <td style="border: 1px solid #ccc; width: 540px;">
            <span style="margin-left: 10px"><strong>Thành tiền:</strong> {{number_format($data['total'] - object_get($data['order'], 'discount',0)) . " đ"}}</span>
        </td>
    </tr>
    <tr style="border: 1px solid #eee;">
        <td style="border: 1px solid #ccc; width: 540px;">
            <span style="margin-left: 10px"><strong>Số tiền bằng chữ:</strong> {{$data['totalConvert']}}</span>
        </td>
    </tr>
    </tbody>
</table>
<br/>
<br/>
<br/>
<br/>
<table style="font-size:8px">
    <tr>
        <td width="200px" class="text-center">Người mua hàng<br/>
            <i>(Ký, ghi rõ họ tên)</i>
        </td>
        <td width="200px">Người bán hàng<br/>
            <i>(Ký, ghi rõ họ tên)</i>
        </td>
        <td width="130px">Trưởng đơn vị<br/>
            <i>(Ký, ghi rõ họ tên)</i>
        </td>
        <td width="130px">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="4">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="4">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="4">&nbsp;</td>
    </tr>
    <tr>
        <td width="150px" class="text-center"></td>
        <td width="150px"></td>
        <td width="130px"></td>
        <td width="130px">&nbsp;</td>
    </tr>
</table>
</body>
</html>
