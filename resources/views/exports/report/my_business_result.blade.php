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
        <th colspan="11" style="text-align: center">BÁO CÁO DOANH SỐ</th>
    </tr>
    <tr>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th>TỪ NGÀY</th>
        <th>{{!empty($from) ? date("d/m/Y", strtotime($from)) : null }}</th>
        <th>ĐẾN NGÀY</th>
        <th>{{!empty($to) ? date("d/m/Y", strtotime($to)) : null }}</th>
    </tr>
    <tr class="header-table">
        <th>STT</th>
        <th>LOẠI KH</th>
        <th>MÃ KH</th>
        <th>TÊN KH</th>
        <th>MỤC TIÊU DOANH SỐ</th>
        <th>DOANH SỐ</th>
        <th>KPI</th>
        {{--        @foreach($promotionList as $name)--}}
        {{--            <th>{{$name}}</th>--}}
        {{--        @endforeach--}}
        <th>TIỀN CHIẾT KHẤU TRỰC TIẾP TRÊN HÓA ĐƠN</th>
        <th>DOANH THU CHÊNH LỆCH</th>
        <th>THƯỞNG DOANH SỐ</th>
        <th>TỔNG THU NHẬP</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($dataTable))
        @foreach($dataTable as $index => $item)
            <tr>
                <td style="border: 1px solid #777">{{ ($index + 1) }}</td>
                @foreach($item as $key=>$value)
                    @if($key == 'promotions')
{{--                        @foreach($value as $pro)--}}
{{--                            <td style="border: 1px solid #777">{{!empty($pro['value']) ? $pro['value'] : ''}}</td>--}}
{{--                        @endforeach--}}
                    @else
                        <td style="border: 1px solid #777">{{!empty($value) ? $value : ''}}</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    @endif
    </tbody>
</table>