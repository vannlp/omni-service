<style>
    thead tr th {
        vertical-align: middle;
    }

    .header-table th {
        text-align: center;
        border: 1px solid #777;
    }
</style>
<?php

?>
<table>
    <thead>
    <tr>
        <th colspan="15" style="text-align: center;font-weight: bold;">BÁO CÁO DANH SÁCH CHO THUÊ MẶT BẰNG</th>
    </tr>
    <tr>
        <th colspan="1" style="font-size: 9px">Ngày tháng: {{date("d/m/Y", time())}}</th>
        {{-- <th colspan="2" style="font-size: 9px">ĐẾN THÁNG: {{date("d/m/Y", strtotime($to ?? time()))}}</th>--}}
    </tr>
    <tr class="header-table">
        <th colspan="1" style="text-align: left; font-weight: bold;">STT</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Tên chủ nhà/Người cần sang nhược</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Số điện thoại</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Địa chỉ mặt bằng </th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Chiều dài(m)</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Chiều ngang(m)</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Chiều sâu</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Đáp án 1</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Đáp án 2</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Đáp án 3</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Đáp án 4</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Đáp án 5</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Hình chụp mặt bằng</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Giá cho thuê mặt bằng</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Có cho thuê gấp</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Mã thành phố</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Tên thành phố</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Mã Quận/Huyện</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Tên Quận/Huyện</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Mã Phường/Xã</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Tên Phường/Xã</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $key+1}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['name']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['phone']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['address']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['length']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['width']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['height']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['area_anwser_1']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['area_anwser_2']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['area_anwser_3']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['area_anwser_4']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['area_anwser_5']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['thumbnail']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['price']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['rush_to_rent']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['city_code']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['city_name']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['district_code']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['district_name']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['ward_code']}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right;">{{ $item['ward_name']}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>