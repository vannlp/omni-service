<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bootstrap Example</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .header td {
            height: 100px;
        }
    </style>
</head>
<body>
<table>
    <tr class="header">
        <td width="200px">
            <p>
                <strong style="text-align: center">
                    <br>CÔNG TY <br>{{\Illuminate\Support\Arr::get($data, 'company.name')}}</strong>
            </p>
        </td>
        <td width="190px">
        </td>
        <td width="150px">
            <img height="80px" width="80px" src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl={{\Illuminate\Support\Arr::get($data, 'shipping_order.order.code')}}">
            <p>Ngày: {{date('d/m/Y', strtotime(array_get($data, 'shipping_order.created_at')))}}</p>
        </td>
    </tr>
</table>
<div style="text-align:center;font-size: 20px"><strong>LỆNH GIAO HÀNG</strong></div>
<div style="text-align:center;font-size: 12px">Mã giao
    hàng: {{\Illuminate\Support\Arr::get($data, 'shipping_order.code')}}</div>
<br>
<table style="font-size:8px">
    <tr>
        <td>
            <p><strong>Họ và tên người
                    mua: </strong> {{\Illuminate\Support\Arr::get($data, 'shipping_order.order.shipping_address_full_name')}}
                <br/>
                <strong>Địa
                    Chỉ: </strong>{{\Illuminate\Support\Arr::get($data, 'shipping_order.order.shipping_address')}}<br/>
                <strong>Giao hàng
                    tại: </strong>{{\Illuminate\Support\Arr::get($data, 'shipping_order.order.shipping_address')}}<br/>
                <strong>Phương thức thanh toán
                    : </strong>{{PAYMENT_METHOD_NAME[\Illuminate\Support\Arr::get($data, 'shipping_order.order.payment_method')]}}<br/>
            </p>
        </td>
        <td>
           <p><strong>Số điện thoại: </strong> {{\Illuminate\Support\Arr::get($data, 'shipping_order.order.shipping_address_phone')}}</p>
        </td>
    </tr>
</table>


<table style="font-size:8px" cellpadding="4px">
    <thead>

    </thead>
    <tbody>
    <div>
        <table border="1" cellpadding="0">
            <tr style="text-align: center">
                {{--<td><strong style="text-align: center">STT</strong></td>--}}

                <th style=" width: 30px; text-align: center"><strong><br/>STT<br/></strong></th>
                <th style="width: 175px; text-align: center"><strong><br/>TÊN SẢN PHẨM<br/></strong></th>
                <th style="width: 55px; text-align: center"><strong><br/>ĐƠN VỊ<br/></strong></th>
                <th style="width: 60px; text-align: center"><strong><br/>SỐ LƯỢNG <br/></strong></th>
                <th style="width: 80px; text-align: center"><strong><br/>ĐƠN GIÁ (bao gồm VAT)<br/></strong></th>
                <th style="width: 120px; text-align: center"><strong><br/>GHI CHÚ<br/></strong></th>
            </tr>
            @php
                $key = 0;
            @endphp
            @foreach($data['shipping_order']['details']  as $key => $shippingOrder)
                <tr>
                    <th style=" width: 30px; text-align: center"><strong><br/>{{$key+1}}<br/></strong></th>
                    <th style=" width: 175px; text-align: center"><strong><br/>{{$shippingOrder['product_name']}}
                            <br/></strong></th>
                    <th style=" width: 55px; text-align: center"><strong><br/>{{$shippingOrder['unit_name']}}
                            <br/></strong></th>
                    <th style=" width: 60px; text-align: center"><strong><br/>{{$shippingOrder['ship_qty']}}
                            <br/></strong></th>
                    <th style=" width: 80px; text-align: center">
                        <strong><br/>{{  number_format(($shippingOrder['price'] * $shippingOrder['ship_qty']), 0, ',', '.')}}
                            <br/></strong></th>
                    <th style=" width: 120px; text-align: center">
                        <strong><br/>{{\Illuminate\Support\Arr::get($data['shipping_order'],'description',null)}}
                            <br/></strong></th>
                </tr>
            @endforeach
        </table>
    </div>
    </tbody>

</table>
<table style="font-size:10px">
    <tr>
        <td width="30px">&nbsp;</td>
        <td width="150px" class="text-center">Nhân viên bán hàng<br/>
            <i>(Ký, ghi rõ họ tên)</i><br/><br/><br/>
            <p></p>
        </td>
        <td width="200px">&nbsp;</td>
        <td width="180px" class="text-center">Trưởng đơn vị<br/>
            <i>(Ký, ghi rõ họ tên)</i>
        </td>
    </tr>
</table>

</body>
</html>