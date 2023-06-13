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

    @media all {
        .page-break {
            display: none;
        }
    }

    @media print {
        .page-break {
            display: block;
            page-break-after: always;
        }
    }
</style>
@foreach($data['details'] as $key => $value)
    <body class="page-break">
    <div><b style="font-size: 14px;width: 100%;text-align: center">PHIẾU GIAO NHẬN VÀ THANH TOÁN</b></div>
    <br/>
    <br/>
    <table class="headers">
        <thead>
        <tr>
            <th width="10%" style="text-align: left">NPP:</th>
            <th width="50%">{{ $data['NPP'] }}</th>
        </tr>
        <tr>
            <th width="10%" style="text-align: left">SĐT:</th>
            <th width="50%">{{ $data['npp_phone'] }}</th>
        </tr>
        <tr>
            <th width="10%" style="text-align: left">Ngày in:</th>
            <th width="25%">{{ date('d/m/Y H:i', strtotime($data['print_date'])) }}</th>
        </tr>
        </thead>
        <thead>
        <tr>
            <th width="10%" style="text-align: left">Số P.GH:</th>
            <th width="50%" style="font-weight: bold">{{ $data['SPGH'] }}</th>
            <th width="15%" style="text-align: left">Ngày P.GH:</th>
            <th width="25%" style="font-weight: bold">{{ date('d/m/Y H:i', strtotime($data['print_date'])) }}</th>
        </tr>
        <tr>
            <th width="13%" style="text-align: left">Khách hàng:</th>
            <th width="47%" style="font-weight: bold">{{ $value['customer_name'] }}</th>
            <th width="15%" style="text-align: left">NVGH:</th>
            <th width="25%" style="font-weight: bold">{{ $data['NVGH'] }}</th>
        </tr>
        <tr>
            <th width="13%" style="text-align: left">SĐT:</th>
            <th width="47%" style="font-weight: bold">{{ $value['customer_phone'] }}</th>
            <th width="15%" style="text-align: left">NVBH:</th>
            <th width="50%" style="font-weight: bold">@if(!empty($data['seller_name'])){{ $data['seller_name'] }}
                -SĐT: {{ $data['seller_phone'] }} @endif</th>
        </tr>
        <tr>
            <th width="100%" style="text-align: left">Địa chỉ giao hàng:
                <strong>{{ $value['shipping_address'] }}</strong></th>
        </tr>
        </thead>

        {{--    <thead>--}}
        {{--    <tr>--}}
        {{--        <th style="text-align: left">Khách hàng:</th>--}}
        {{--        <th style="font-weight: bold">{{ $data['customer_name'] }}</th>--}}
        {{--    </tr>--}}
        {{--    </thead>--}}
        {{--    <thead>--}}
        {{--    <tr>--}}
        {{--        <th width="20%" style="text-align: left">Địa chỉ giao hàng:</th>--}}
        {{--        <th width="80%"--}}
        {{--            style="text-align: left; font-weight: bold;">{{ !empty($data['shipping_address']) ? $data['shipping_address'] : $data['street_address'] ?? null }}</th>--}}
        {{--    </tr>--}}
        {{--    </thead>--}}
    </table>

    <br>
    <br>
    {{--    <h4 style="font-weight: bold; text-align: left">1.Hàng bán</h4>--}}
    <table style="width:100%; text-align: center" class="table-border">
        <tr class="tbody-table">
            <th rowspan="2" style="width:20%; font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">
                Mã sản phẩm
            </th>
            {{--        <th rowspan="2" style="width:20%; font-size: 8px; text-align: center; font-weight: bold">--}}
            {{--            <div style="padding-top: 10px">Mô tả</div>--}}
            {{--        </th>--}}
            <th rowspan="2" style="width:40%; font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">
                Tên hàng
            </th>
            {{--            <th rowspan="2" style="width:6%; font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">--}}
            {{--                QC--}}
            {{--            </th>--}}
            <th colspan="1" style="width:12%; font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">
                Số lượng
            </th>
            {{--            <th rowspan="2" style="width:12%; font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">--}}
            {{--                Giá thùng--}}
            {{--            </th>--}}
            <th rowspan="2" style="width:14%; font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">
                Giá lẻ
            </th>
            <th rowspan="2" style="width:14%; font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">
                Thành tiền
            </th>
            {{--            <th colspan="2" style="width:20%; font-size: 8px; text-align: center; font-weight: bold">Chiết khấu</th>--}}
            {{--            <th rowspan="2" style="width: 12%; font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">--}}
            {{--                Thanh toán--}}
            {{--            </th>--}}
        </tr>
        <tr class="tbody-table">
            <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold;">Đôi</th>
            {{--            <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold;">Lẻ</th>--}}
            {{--            <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold">Mua hàng</th>--}}
            {{--            <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold">Khác</th>--}}
        </tr>
        @foreach($value['details'] as $item)
            <tr>
                <td style="font-size: 8px;line-height: 20px; text-align: center">{{ $item['product_code'] }}</td>
                <td style="font-size: 8px;line-height: 20px; text-align: center">{{ $item['product_name'] }}</td>
                {{--                <td style="font-size: 8px; text-align: center;line-height: 20px">{{ $item['QC'] }}</td>--}}
                {{--                <td style="font-size: 8px; text-align: center;line-height: 20px">{{ $item['crates'] }}</td>--}}
                <td style="font-size: 8px; text-align: center;line-height: 20px">{{ $item['qty'] }}</td>
                <td style="font-size: 8px; text-align: right;line-height: 20px">{{ number_format($item['price']) . " đ" }}</td>
                {{--                <td style="font-size: 8px; text-align: right;line-height: 20px">{{ number_format($item['price']/$item['QC']) . " đ" }}</td>--}}
                <td style="font-size: 8px; text-align: right;line-height: 20px">{{ number_format($item['total']) . " đ" }}</td>
                {{--                <td style="font-size: 8px;line-height: 20px">{{ !empty($item['mua_hang']) ? $item['mua_hang'] : ""  }}</td>--}}
                {{--                <td style="font-size: 8px;line-height: 20px">{{ !empty($item['khac']) ? $item['khac'] : "" }}</td>--}}
                {{--                <td style="font-size: 8px; text-align: right;line-height: 20px">{{ number_format($item['payment']) . " đ" ?? "" }}</td>--}}
            </tr>
        @endforeach
    </table>
    {{--    <p style="text-align: left;">*Ghi chú: {{$value['description'] ?? ""}}</p>--}}
    {{--    <br>--}}
    {{--    <br>--}}
    <table class="headers" style="float: right">
        <thead>
        <tr class="header-table">
            <th width="80%" style="text-align: right">Tổng tiền:</th>
            <th width="20%"
                style="text-align: right">{{ !empty($value['totalPrice']) ? number_format($value['totalPrice']) . " đ" : "" }}</th>
        </tr>
        </thead>
        <thead>
        <tr class="header-table">
            <th width="80%" style="text-align: right">Chiết khấu:</th>
            <th width="20%"
                style="text-align: right">{{ !empty($value['totalPurchaseDiscount']) ? number_format($data['totalPurchaseDiscount']) . " đ" : "" }}</th>
        </tr>
        </thead>
        {{--        <thead>--}}
        {{--        <tr class="header-table">--}}
        {{--            <th width="80%" style="text-align: right">Tổng chiết khấu khác:</th>--}}
        {{--            <th width="20%"--}}
        {{--                style="text-align: right">{{ !empty($value['totalOtherDiscount']) ? number_format($data['totalOtherDiscount']) . " đ" : "" }}</th>--}}
        {{--        </tr>--}}
        {{--        </thead>--}}
        {{--        <thead>--}}
        {{--        <tr class="header-table">--}}
        {{--            <th width="80%" style="text-align: right">Trả thưởng khác:</th>--}}
        {{--            <th width="20%" style="text-align: right"></th>--}}
        {{--        </tr>--}}
        {{--        </thead>--}}
        <thead>
        <tr class="header-table">
            <th width="80%" style="text-align: right">Tổng tiền thanh toán:</th>
            <th width="20%"
                style="text-align: right">{{  !empty($value['totalPayment']) ? number_format($value['totalPayment']) . " đ" : "" }}</th>
        </tr>
        </thead>
    </table>
    <div style="margin: 15% 0 0 5%;">
        Số tiền bằng chữ:
        <strong>{{ !empty($value['totalPaymentConvert']) ? $value['totalPaymentConvert'] . " đồng" : "" }}</strong>
    </div>
    {{--    <h4 style="font-weight: bold; text-align: left">2.Hàng khuyến mãi/trả thưởng CT.HTTM</h4>--}}
    {{--    <table style="width:100%" class="table-border">--}}
    {{--        <tr class="tbody-table">--}}
    {{--            <th rowspan="2" style="width:15%; font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">--}}
    {{--                Mặt--}}
    {{--                hàng--}}
    {{--            </th>--}}
    {{--            <th rowspan="2" style="width:40%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">--}}
    {{--                Tên--}}
    {{--                hàng--}}
    {{--            </th>--}}
    {{--            <th rowspan="2" style="width:12%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">--}}
    {{--                QC--}}
    {{--            </th>--}}
    {{--            <th colspan="2" style="width:18%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">--}}
    {{--                Số--}}
    {{--                lượng--}}
    {{--            </th>--}}
    {{--            --}}{{--            <th rowspan="2" style="width:15%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">--}}
    {{--            --}}{{--                Đơn--}}
    {{--            --}}{{--                giá--}}
    {{--            --}}{{--            </th>--}}
    {{--            <th rowspan="2" style="width:15%; font-size: 8px; text-align: center; font-weight: bold;line-height: 15px">--}}
    {{--                Chương trình KM--}}
    {{--            </th>--}}
    {{--        </tr>--}}
    {{--        <tr class="tbody-table">--}}
    {{--            <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold">Thùng</th>--}}
    {{--            <th style="font-size: 8px; text-align: center; padding-top: 5px; font-weight: bold">Lẻ</th>--}}
    {{--        </tr>--}}
    {{--    </table>--}}
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
@endforeach
</html>