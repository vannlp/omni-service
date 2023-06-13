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
        <th style="text-align: left;font-weight: bold; font-size: 20px">BÁO CÁO ĐƠN HÀNG NUTISHOP</th>
    </tr>
    <tr>
        <th style="font-size: 10px;font-weight: bold">TỪ : {{!empty($from) ? date("d/m/Y", strtotime($from ?? time())) : ''}}</th>
        <th style="font-size: 10px;font-weight: bold">ĐẾN : {{!empty($to) ? date("d/m/Y", strtotime($to ?? time())) : ''}}</th>
    </tr>
    <tr class="header-table">
        <th style="text-align: left; font-weight: bold;">STT</th>
        <th style="text-align: left; font-weight: bold;">Kênh bán hàng</th>
        <th style="text-align: left; font-weight: bold;">Mã nhà phân phối</th>
        <th style="text-align: left; font-weight: bold;">Tên nhà phân phối</th>
        <th style="text-align: left; font-weight: bold;">Mã phường nhà phân phối</th>
        <th style="text-align: left; font-weight: bold;">Tên phường nhà phân phối</th>
        <th style="text-align: left; font-weight: bold;">Mã quận nhà phân phối</th>
        <th style="text-align: left; font-weight: bold;">Tên quận nhà phân phối</th>
        <th style="text-align: left; font-weight: bold;">Mã tỉnh nhà phân phối</th>
        <th style="text-align: left; font-weight: bold;">Tên tỉnh nhà phân phối</th>
        <th style="text-align: left; font-weight: bold;">Mã khách hàng</th>
        <th style="text-align: left; font-weight: bold;">Tên khách hàng</th>
        <th style="text-align: left; font-weight: bold;">SĐT Khách hàng</th>
        <th style="text-align: left; font-weight: bold;">Địa chỉ nhận hàng</th>
        <th style="text-align: left; font-weight: bold;">Phường/Xã nhận hàng</th>
        <th style="text-align: left; font-weight: bold;">Quận/Huyện nhận hàng</th>
        <th style="text-align: left; font-weight: bold;">Tỉnh thành phố nhận hàng</th>
        <th style="text-align: left; font-weight: bold;">Mã đơn hàng</th>
        <th style="text-align: left; font-weight: bold;">Loại đơn hàng</th>
        <th style="text-align: left; font-weight: bold;">Dịch vụ giao hàng</th>
        <th style="text-align: left; font-weight: bold;">Số ngày chưa xử lí đơn</th>
        <th style="text-align: left; font-weight: bold;">Khách trả phí vận chuyển</th>
        <th style="text-align: left; font-weight: bold;">Shop trả phí vận chuyển</th>
        <th style="text-align: left; font-weight: bold;">Tổng phí vận chuyển</th>
        <th style="text-align: left; font-weight: bold;">Tạm tính</th>
        @if(!empty($data))
            @foreach($data['promo'] as $key => $item)
                {{--                @foreach($item['promotion_code'] as $key => $i)--}}
                <th style="text-align: left; font-weight: bold;">{{$item}}</th>
                {{--                @endforeach--}}
            @endforeach
        @endif
        <th style="text-align: left; font-weight: bold;background-color: yellow">Thành tiền</th>
        <th style="text-align: left; font-weight: bold;">Mã thanh toán</th>
        <th style="text-align: left; font-weight: bold;">Phương thức thanh toán</th>
        <th style="text-align: left; font-weight: bold;">Đơn vị vận chuyển</th>
        <th style="text-align: left; font-weight: bold;">Trạng thái giao hàng</th>
        <th style="text-align: left; font-weight: bold;">Trạng thái đơn hàng</th>
        <th style="text-align: left; font-weight: bold;">Trạng thái thanh toán</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['stt']) ? $item['stt'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['order_channel']) ? $item['order_channel'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['distributor_code']) ? $item['distributor_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['distributor_name']) ? $item['distributor_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['ward_code']) ? $item['ward_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['ward_name']) ? $item['ward_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['district_code']) ? $item['district_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['district_name']) ? $item['district_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['city_code']) ? $item['city_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['city_name']) ? $item['city_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['customer_code']) ? $item['customer_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['customer_name']) ? $item['customer_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['customer_phone']) ? $item['customer_phone'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['shipping_address']) ? $item['shipping_address'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['ward']) ? $item['ward'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['district']) ? $item['district'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['city']) ? $item['city'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['order_code']) ? $item['order_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['order_type']) ? $item['order_type'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['lading_method']) ? $item['lading_method'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['date_time']) ? $item['date_time'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['ship_fee_customer']) ? $item['ship_fee_customer'] : 0}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['ship_fee_shop']) ? $item['ship_fee_shop'] : 0}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['ship_fee_total']) ? $item['ship_fee_total'] : 0}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['sub_total_price']) ? $item['sub_total_price'] : 0}}</td>
                @foreach($data['promo'] as $key => $i)
                    @if(!empty($item['value']))
                            <td style="border: 1px solid #777; text-align: left"><?php 
                            foreach($item['value'] as $k => $val){
                                $price_promo = $val;
                            }
                            if(!empty($item['promotioncode'])){
                                if(in_array($i, $item['promotioncode'])){
                                    echo $price_promo;
                                }
                                else{
                                    echo 0;
                                }
                            }
                            else{
                                echo 0;
                            }
                            
                                ?></td>
                    @else
                        <td style="border: 1px solid #777; text-align: left">0</td>
                    @endif
                @endforeach
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_price']) ? $item['total_price'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['payment_code']) ? $item['payment_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['payment_method']) ? $item['payment_method'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['shipping_method_name']) ? $item['shipping_method_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['shipping_order_status']) ? $item['shipping_order_status'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['status']) ? $item['status'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['payment_status']) == 1 ? 'Đã thanh toán' : 'Chưa thanh toán'}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>