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
        <th colspan="12" style="text-align: center;font-weight: bold;">Danh sách quy cách</th>
    </tr>
    <tr>
        {{-- <th colspan="2" style="font-size: 9px">TỪ THÁNG: {{date("d/m/Y", strtotime($from ?? time()))}}</th>--}}
        {{-- <th colspan="2" style="font-size: 9px">ĐẾN THÁNG: {{date("d/m/Y", strtotime($to ?? time()))}}</th>--}}
    </tr>
    <tr class="header-table">
        <th colspan="2" style="text-align: left; font-weight: bold;">Mã quy cách</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Tên quy cách</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td colspan="2" style="border: 1px solid #777; text-align: left">{{!empty($item['code']) ? $item['code'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{ !empty($item['value']) ? $item['value'] : ''}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>