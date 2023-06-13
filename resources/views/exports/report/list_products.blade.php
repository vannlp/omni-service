<style>
    thead tr th {
        vertical-align: middle;
    }

    .header-table th {
        text-align: center;
        border: 1px solid #777;
    }
</style>
<table>
    <thead>
    <tr>
        <th colspan="12" style="text-align: center;font-weight: bold;">Danh sách sản phẩm</th>
    </tr>
    <tr>
{{--        <th colspan="2" style="font-size: 9px">TỪ THÁNG: {{date("d/m/Y", strtotime($from ?? time()))}}</th>--}}
{{--        <th colspan="2" style="font-size: 9px">ĐẾN THÁNG: {{date("d/m/Y", strtotime($to ?? time()))}}</th>--}}
    </tr>
    <tr class="header-table">
        <th style="text-align: left; font-weight: bold; width: 10px">STT</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Mã sản phẩm</th>
        <th style="text-align: left; font-weight: bold; width: 80px">Tên sản phẩm</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Giá</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Giá khuyến mãi</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Trạng thái sản phẩm</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Số lượng tồn</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Số lần quét QR Code</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Mô tả ngắn</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Mô tả dài</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Chiều dài</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Chiều rộng</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Chiều cao</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Đơn vị tính kích thước</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Khối lượng</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Đơn vị tính khối lượng </th>
        <th style="text-align: left; font-weight: bold; width: 20px">Số lượng đã bán</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Đơn vị tính</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Quy cách</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Độ tuổi</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Dung tích</th>
        <th style="text-align: left; font-weight: bold; width: 20px">HSD(Tháng)</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Industry</th>
        <th style="text-align: left; font-weight: bold; width: 20px">CADCODE</th>
        <th style="text-align: left; font-weight: bold; width: 20px">CAT</th>
        <th style="text-align: left; font-weight: bold; width: 20px">SubCat</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Manufacture</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Brand</th>
        <th style="text-align: left; font-weight: bold; width: 20px">Brandy</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            @if(!empty($item['cat']))
            <tr>
                <td style="border: 1px solid #777; text-align: left; ">{{ $key+1 }}</td>
                <td style="border: 1px solid #777; text-align: right;">{{ !empty($item['code']) ? $item['code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left; ">{{!empty($item['name']) ? $item['name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['price']) ? $item['price'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['special']) ? $item['special'] : $item['price']}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['status']) == 1 ? "Bật" : "Tắt"}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['warehouse_quantity']) ? $item['warehouse_quantity'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['qr_scan']) ? $item['qr_scan'] : 0}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['short_des']) ? $item['short_des'] : ""}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{htmlspecialchars_decode($item['des'])}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['length']) ? $item['length'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['width']) ? $item['width'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['height']) ? $item['height'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['length_class']) ? $item['length_class'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['weight']) ? $item['weight'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['weight_class']) ? $item['weight_class'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['sold_count']) ? $item['sold_count'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['unit_name']) ? $item['unit_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['specification_value']) ? $item['specification_value'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['get_age_name']) ? $item['get_age_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['capacity']) ? $item['capacity'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['expiry_date']) ? $item['expiry_date'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['area_name']) ? $item['area_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['cadcode']) ? $item['cadcode'] : ''}}</td>

                            <td  style="border: 1px solid #777; text-align: right;">{{!empty($item['cat']) ? $item['cat'] : ''}}</td>
                            <td style="border: 1px solid #777; text-align: right;">{{!empty($item['sub_cat']) ? $item['sub_cat'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['get_manufacture_code']) ? $item['get_manufacture_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['brand_name']) ? $item['brand_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right;">{{!empty($item['child_brand_name']) ? $item['child_brand_name'] : ''}}</td>
            </tr>
            @endif
        @endforeach
    @endif
    </tbody>
</table>