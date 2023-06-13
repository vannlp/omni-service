@php use App\V1\Traits\ControllerTrait; @endphp
        <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<style>
    /*thead tr th {*/
    /*    vertical-align: middle;*/
    /*    padding-top: 10px;*/
    /*}*/


    /*.header-table th {*/
    /*    text-align: center;*/
    /*    !*border: 1px solid #777;*!*/
    /*}*/

    .table-border {
        border: 1px solid black;
        border-collapse: collapse;
    }

    .headers tr th {
        text-align: left;
    }

    .table-border td {
        border: 1px solid black;
        border-collapse: collapse;
        padding-top: 10px;
    }

    .table-border th {
        border: 1px solid black;
        border-collapse: collapse;
    }

    h2 {
        text-align: center;
    }
</style>

<body>
<h2 align="center">PHIẾU XUẤT KHO</h2>
{{--<div></div>--}}
{{--<div></div>--}}
<table class="headers">
    <thead>
    <tr>
        <th width="60%" style="text-align: left">NPP: {{ $data['NPP']  }} </th>
    </tr>
    <tr>
        <th width="60%" style="text-align: left">SĐT: {{ $data['npp_phone']  }}</th>
    </tr>
    <tr>
        <th width="60%" style="text-align: left">Ngày in: {{ date('d/m/Y H:i', strtotime($data['print_date'])) }}</th>
    </tr>
    <tr>
        <th width="100%" style="text-align: left">Số P.GH: <strong>{{ $data['SPGH'] }}</strong></th>
    </tr>
    </thead>
</table>

<br>
<h4 style="font-weight: bold; text-align: left">1. Hàng bán</h4>
<table style="width:100%;" class="table-border">
    <tr class="tbody-table">
        <th rowspan="2" style="width:15%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">Mã
            hàng
        </th>
        <th rowspan="2" style="width:25%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">Tên
            hàng
        </th>
        <th rowspan="2" style="width:12%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">QC
        </th>
        <th colspan="2" style="width:18%; font-size: 8px; text-align: center; font-weight: bold">Số lượng</th>
        <th rowspan="2" style="width:15%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">Đơn
            giá
        </th>
        <th rowspan="2" style="width:15%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">Thành
            tiền
        </th>
        {{--        <th colspan="2" style="width:20%; font-size: 8px; text-align: center; font-weight: bold">Chiết khấu</th>--}}
        {{--        <th rowspan="2" style="width: 11%; font-size: 8px; text-align: center; font-weight: bold">Thanh toán</th>--}}
    </tr>
    <tr class="tbody-table">
        <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold">Thùng</th>
        <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold">Lẻ</th>
        {{--        <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold">Mua hàng</th>--}}
        {{--        <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold">Khác</th>--}}
    </tr>
    @foreach($data['details'] as $item)
        <tr>
            <td style="font-size: 8px; text-align: center;line-height: 20px;">{{ $item['product_code'] }}</td>
            <td style="font-size: 8px; text-align: center;">{{ $item['product_name'] }}</td>
            <td style="font-size: 8px; text-align: center;line-height: 20px;">{{ $item['QC'] }}</td>
            <td style="font-size: 8px; text-align: center;line-height: 20px;">{{ $item['crates'] }}</td>
            <td style="font-size: 8px; text-align: center;line-height: 20px;">{{ !empty($item['le']) ? $item['le'] : "" }}</td>
            <td style="font-size: 8px; text-align: left;line-height: 20px;">{{ number_format($item['price']) . " đ"}}</td>
            <td style="font-size: 8px; text-align: right;line-height: 20px;">{{ number_format($item['total']) . " đ"}}</td>
            {{--            <td style="font-size: 8px">{{ !empty($item['mua_hang']) ? $item['mua_hang'] : ""  }}</td>--}}
            {{--            <td style="font-size: 8px">{{ !empty($item['khac']) ?$item['khac'] : "" }}</td>--}}
            {{--            <td style="font-size: 8px; text-align: right;">{{ number_format($item['payment']) . " đ" ?? "" }}</td>--}}
        </tr>
    @endforeach
</table>
<br>
<br>
<table class="headers" style="float: right">
    <thead>
    <tr class="header-table">
        <th width="80%" style="text-align: right; font-weight: bold">Tổng Tiền:</th>
        <th width="20%"
            style="text-align: right">{{ !empty($data['totalPrice']) ? number_format($data['totalPrice']) . " đ" : "" }}</th>
    </tr>
    </thead>
    <thead>
    <tr class="header-table">
        {{--        <th width="80%" style="text-align: right; font-weight: bold">Tổng Chiết Khấu Mua Hàng:</th>--}}
        <th width="80%" style="text-align: right; font-weight: bold">Tổng Chiết Khấu:</th>
        <th width="20%"
            style="text-align: right">{{ !empty($data['totalPurchaseDiscount']) ? number_format($data['totalPurchaseDiscount']) . " đ" : "" }}</th>
    </tr>
    </thead>
    {{--    <thead>--}}
    {{--    <tr class="header-table">--}}
    {{--        <th width="80%" style="text-align: right; font-weight: bold">Tổng Chiết Khấu Khác:</th>--}}
    {{--        <th width="20%" style="text-align: right">{{ !empty($data['totalOtherDiscount']) ? number_format($data['totalOtherDiscount']) . " đ" : "" }}</th>--}}
    {{--    </tr>--}}
    {{--    </thead>--}}
    {{--    <thead>--}}
    {{--    <tr class="header-table">--}}
    {{--        <th width="80%" style="text-align: right; font-weight: bold">Trả thưởng Khác:</th>--}}
    {{--        <th width="20%" style="text-align: right"></th>--}}
    {{--    </tr>--}}
    {{--    </thead>--}}
    <thead>
    <tr class="header-table">
        <th width="80%" style="text-align: right; font-weight: bold">Tổng tiền thanh toán:</th>
        <th width="20%"
            style="text-align: right">{{  !empty($data['totalPayment']) ? number_format($data['totalPayment']) . " đ" : "" }}</th>
    </tr>
    </thead>
</table>
<br>
<div style="margin: 15% 0 0 5%;">
    <p>Số tiền bằng chữ: <strong>{{ !empty($data['totalPaymentConvert']) ? $data['totalPaymentConvert'] . " đồng" : "" }}</strong></p>
</div>
<br>
<h4 style="font-weight: bold; text-align: left">2.Hàng khuyến mãi/trả thưởng CT.HTTM</h4>
<table style="width:100%" class="table-border">
    <tr class="tbody-table">
        <th rowspan="2" style="width:15%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">Mã
            hàng
        </th>
        <th rowspan="2" style="width:25%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">Tên
            hàng
        </th>
        <th rowspan="2" style="width:12%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">QC
        </th>
        <th colspan="2" style="width:18%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">Số
            lượng
        </th>
        <th rowspan="2" style="width:15%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">Đơn
            giá
        </th>
        <th rowspan="2" style="width:15%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">
            Chương trình KM
        </th>
    </tr>
    <tr class="tbody-table">
        <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold">Thùng</th>
        <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold">Lẻ</th>
    </tr>
</table>
<br>
<br>
<table>
    <thead>
    <tr>
        <th style="font-weight: bold">Kế toán</th>
        <th width="32%" style="font-weight: bold">NV giao hàng</th>
        <th width="32%" style="font-weight: bold">KH ký nhận</th>
        <th style="font-weight: bold">Ghi chú</th>
    </tr>
    </thead>
</table>

</body>
</html>