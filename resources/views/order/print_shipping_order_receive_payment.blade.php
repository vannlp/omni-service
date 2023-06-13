@php use App\V1\Traits\ControllerTrait; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<style>
    /* @media all {
        .page-break {
            display: none;
        }
    } 

    @media print {
        .page-break {
            display: block;
            page-break-after: always;
        }
    } */
</style>
@foreach($data['details'] as $key => $value)@endforeach


<body>
{{--    <table border="0" width="100%" >--}}
{{--        <tbody>--}}
{{--            <tr>--}}
{{--                <td width="40%"><div valign="top">&nbsp;&nbsp;<img width="130" height="40" src="{{ URL::to('report_ntf/logo_ntf.png') }}" alt="image"><br><b>&nbsp; Hotline: 02838 255 777</b></div></td>--}}
{{--                <td width="60%">--}}
{{--                    <div align="center">--}}
{{--                        <b>CTY CỔ PHẦN THỰC PHẨM DINH DƯỠNG NUTIFOOD PHIẾU GIAO HÀNG</b>--}}
{{--                    </div>--}}
{{--                </td>--}}
{{--            </tr>--}}
{{--            <br>--}}
{{--            <tr>--}}
{{--                <td colspan="2">--}}
{{--                    <img width="669" height="150" src="{{ URL::to('report_ntf/loi_tri_an.png') }}" alt="image"><br>--}}
{{--                </td>--}}
{{--            </tr>--}}
{{--        </tbody>--}}
{{--    </table><br>--}}

<table border="1"  width="100%" style="font-size: 8px">
    <tbody>
        <tr>
            <td width="28%" align="center">
               <b>Thông Tin Người Gửi</b>
            </td>
            <td width="72%" align="center">
                <b>Thông tin Khách Hàng</b>
            </td>
        </tr>
        <tr style="text-align: left;">
            <td width="28%" ><b>·</b>&nbsp;Nơi xuất hàng: <b>{{ $data['NPP'] }}</b><br><b>·</b>&nbsp;SĐT: <b>{{ $data['npp_phone'] }}</b><br><b>·</b>&nbsp;Ngày in phiếu: <b> {{ date('d/m/Y H:i', strtotime($data['print_date'])) }}</b><br></td>
{{--                    <li>NPP: {{ $data['NPP'] }}</li><li>SĐT: <b>{{ $data['npp_phone'] }}</b></li><li>Ngày in phiếu: {{ date('d/m/Y H:i', strtotime($data['print_date'])) }}</li></ul></td>--}}
            <td width="72%"><b>·</b>&nbsp;Khách hàng: <b>{{ $value['customer_name'] }}</b><br><b>·</b>&nbsp;Địa chỉ giao hàng: <b>{{ $value['shipping_address'] }}</b><br><b>·</b>&nbsp;Mã đơn hàng: <b>{{ $data['SPGH'] }}</b><br><b>·</b>&nbsp;Ngày đặt hàng: <b>{{ $value['create'] }}</b><br><b>·</b>&nbsp;Ghi chú đơn hàng: <b>{{$value['description'] ?? ""}}</b></td>
{{--                <ul>--}}
{{--                    <li>Khách hàng: <b>{{ $value['customer_name'] }}</b></li>--}}
{{--                    <li>Địa chỉ giao hàng: <b>{{ $value['shipping_address'] }}</b></li>--}}
{{--                    <li>Mã đơn hàng: <b>{{ $data['SPGH'] }}</b></li>--}}
{{--                    <li>Ngày đặt hàng:&nbsp;{{ $value['create'] }}</li>--}}
{{--                    <li>Chi chú đơn hàng:&nbsp;{{$value['description'] ?? ""}}</li>--}}
{{--                </ul>--}}

        </tr>
        <tr>
            <td width="100%" valign="top" colspan="2">
                <b>Đơn vị vận chuyển:</b><b>&nbsp;</b>{{!empty($data['shipping_method']) ? ($data['shipping_method']): ''}}<br>
                <b>Mã vận đơn</b><b>:</b><b>&nbsp;</b>{{!empty($data['shipping_method_code']) ? ($data['shipping_method_code']): ''}}<br>
                <b>Thông tin NV giao hàng:</b><b>&nbsp;</b>{{!empty($data['seller_shipping']) ? "Tên:". ($data['seller_shipping']): ''}}&nbsp; {{!empty($data['seller_shipping_phone']) ? "- SĐT:".($data['seller_shipping_phone']): ''}}<b></b>
            </td>
        </tr>
{{--        <tr>--}}
{{--            <td width="100%" valign="top" colspan="2">--}}
{{--                <b>Mã vận đơn</b><b>:</b><b>&nbsp;</b>{{!empty($data['shipping_method_code']) ? ($data['shipping_method_code']): ''}}--}}
{{--            </td>--}}
{{--        </tr>--}}
{{--        <tr>--}}
{{--            <td width="100%" valign="top" colspan="2">--}}
{{--                <b>Thông tin NV giao hàng:</b><b>&nbsp;</b>{{!empty($data['seller_shipping']) ? "Tên:". ($data['seller_shipping']): ''}}&nbsp; {{!empty($data['seller_shipping_phone']) ? "- SĐT:".($data['seller_shipping_phone']): ''}}<b></b>--}}
{{--            </td>--}}
{{--        </tr>--}}
    </tbody>
</table><br><br>
<table border = "1" cellpadding="2" width ="100%" style="font-size: 8px;border-collapse: collapse;" >
        <tr class="tbody-table">
            <td width="5%" style="font-size: 8px; text-align: center; font-weight: bold;line-height: 17px;" >
                <b>STT</b>
            </td>
            <td width="60%" style="font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">
                <b>Tên Sản Phẩm</b>
            </td>
            <td width="10%" style="font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">
                <b >Số lượng</b>
            </td>
            <td width="10%" style="font-size: 8px; text-align: center; font-weight: bold;line-height: 17px">
                <b>Đơn giá</b>
            </td>
            <td width="15%" style="font-size: 8px; text-align: center; font-weight: bold;line-height: 17px" >
                <b>Thành Tiền</b>
            </td>
        </tr>
    <?php $i= 1?>
        @foreach($value['details'] as $item)

        <tr>
            <td  style="font-size: 8px; text-align: center;">{{$i++}}</td>
            <td  style="font-size: 8px; text-align: left;">{{$item['product_name']}}</td>
            <td  style="font-size: 8px; text-align: center;">{{!empty($item['qty']) ? ($item['qty']) : ''}}</td>
            <td  style="font-size: 8px; text-align: right;">{{!empty($item['price']) ? number_format($item['price']) : ''}}</td>
            <td  style="font-size: 8px; text-align: right;">{{!empty($item['total']) ? number_format($item['total']) : ''}}</td>
        </tr>
        @endforeach
        <tr>
            <th  valign="center"  width="32.5%"><b>Tổng trọng lượng: </b> {{ !empty($data['weight']) ? number_format($data['weight']) : "" }}kg<b></b></th>
            <th  valign="center"  width="32.5%"><b>Phương thức thanh toán:</b><b>&nbsp;</b>{{ !empty($data['payment_method']) ? ($data['payment_method']) : "" }}<b></b></th>
            <th width="20%" valign="top" align="left" colspan="1">
                <b>Tổng tiền hàng:</b>
            </th>
            <td width="15%" valign="top" align="right">
                {{ !empty($value['totalPaymentNoDiscount']) ? number_format($value['totalPaymentNoDiscount']): "" }}
            </td>
        </tr>
        <tr>
            <th width="65%" colspan="1" rowspan="4"><b style="text-align: left">Tiền thu hộ:</b><p><b style="font-size: 26px;text-align: right;line-height: 45px">{{ !empty($data['payment_method']) && $data['payment_method']!= 'CASH'? "0 VND" : number_format($value['totalPayment'])." VND"}}</b></p></th>
{{--            <th  valign="center"  width="30%"></th>--}}
            <th width="20%" valign="top" align="left" >
               <b>Phí vận chuyển:</b>
            </th>
            <td width="15%" valign="top" align="right">
                {{ !empty($data['ship_fee']) ? number_format($data['ship_fee']) : "" }}
            </td>
        </tr>
        <tr>
            <th width="20%" valign="top" align="left" colspan="1">
               <b>Khuyến mãi:</b>
            </th>
            <td width="15%" valign="top" align="right">
                {{ !empty($value['totalPurchaseDiscount']) ? number_format(-1 *$value['totalPurchaseDiscount']) : "" }}
            </td>
        </tr>
        <tr>
            <th width="20%" valign="top" align="left" colspan="1">
                <b>Trợ phí vận chuyển:</b>
            </th>
            <td width="15%" valign="top" align="right">
                {{ !empty($data['ship_store']) ? number_format(-1 *$data['ship_store']) : "" }}
            </td>
        </tr>
        <tr>
            <th width="20%" valign="top"  align="left" colspan="1">
                <b>Tổng thanh toán:</b>
            </th>
            <td width="15%" valign="top"  align="right">
                {{ !empty($value['totalPayment']) ? number_format($value['totalPayment']) : "" }}
            </td>
        </tr>
</table><br><br><table border="1"  width = "100%" style="font-size: 8px">
            <tr>
                <td width="25%" valign="top" style="line-height: 20px"><b align="center" >Kế Toán Kho</b></td>
                <td width="25%" valign="top" style="line-height: 20px"><b align="center">Nhân Viên Kho</b></td>
                <td width="25%" valign="top" style="line-height: 20px"><b align="center">Đơn Vị Vận Chuyển Ký Nhận</b></td>
                <td width="25%" valign="top" style="line-height: 20px"><b align="center">Khách Hàng Ký Nhận</b></td>
            </tr>

            <tr>
                <td width="25%" valign="top" height= "45px">
                </td>
                <td width="25%" valign="top" height = "45px">
                </td>
                <td width="25%" valign="top" height = "45px">
                </td>
                <td width="25%" valign="top" height = "45px">
                </td>
            </tr>
    </table>
</body>

</html>