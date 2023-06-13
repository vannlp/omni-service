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
        <th colspan="12" style="text-align: center;font-weight: bold;">Danh sách bảng giá</th>
    </tr>
    <tr>
        {{-- <th colspan="2" style="font-size: 9px">TỪ THÁNG: {{date("d/m/Y", strtotime($from ?? time()))}}</th>--}}
        {{-- <th colspan="2" style="font-size: 9px">ĐẾN THÁNG: {{date("d/m/Y", strtotime($to ?? time()))}}</th>--}}
    </tr>
    <tr class="header-table">
        <th colspan="2" style="text-align: left; font-weight: bold;">Mã giảm giá</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Tên bảng giá</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Từ ngày</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Đến ngày</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Trạng thái bảng giá</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Thứ tự</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Mã nhóm đối tượng</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td colspan="2"
                    style="border: 1px solid #777; text-align: left">{{!empty($item['code']) ? $item['code'] : ''}}</td>
                <td colspan="2"
                    style="border: 1px solid #777; text-align: right">{{ !empty($item['name']) ? $item['name'] : ''}}</td>
                <td colspan="2"
                    style="border: 1px solid #777; text-align: right">{{ !empty($item['from']) ? $item['from'] : ''}}</td>
                <td colspan="2"
                    style="border: 1px solid #777; text-align: right">{{ !empty($item['to']) ? $item['to'] : ''}}</td>
                <td colspan="2"
                    style="border: 1px solid #777; text-align: right">{{ !empty($item['status']) ? $item['status'] : ''}}</td>
                <td colspan="2"
                    style="border: 1px solid #777; text-align: right">{{ !empty($item['order']) ? $item['order'] : ''}}</td>
                <td colspan="2"
                    style="border: 1px solid #777; text-align: right">{{ !empty($item['group_ids']) ? $item['group_ids'] : ''}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>