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
        <th style="text-align: left;font-weight: bold; font-size: 20px">BÁO CÁO CHI TIẾT ĐƠN HÀNG CỦA CALLER NUTISHOP</th>
    </tr>
    <tr>
        <th style="font-size: 10px;font-weight: bold">TỪ : {{!empty($from) ? date("d/m/Y", strtotime($from ?? time())) : ''}}</th>
        <th style="font-size: 10px;font-weight: bold">ĐẾN : {{!empty($to) ? date("d/m/Y", strtotime($to ?? time())) : ''}}</th>
    </tr>
    <tr class="header-table">
        <th style="text-align: left; font-weight: bold;">STT</th>
        <th style="text-align: left; font-weight: bold;">Agent</th>
        <th style="text-align: left; font-weight: bold;">Leader</th>
        <th style="text-align: left; font-weight: bold;">Data nhận</th>
        <th style="text-align: left; font-weight: bold;">Hoàn tất – Chốt đơn thành công</th>
        <th style="text-align: left; font-weight: bold;">Hoàn tất - KH hủy đơn</th>
        <th style="text-align: left; font-weight: bold;">KH không liên lạc được lần 1</th>
        <th style="text-align: left; font-weight: bold;">KH không liên lạc được lần 2</th>
        <th style="text-align: left; font-weight: bold;">Hoàn tất – Không liên lạc được</th>
        <th style="text-align: left; font-weight: bold;">Data pending</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
                <tr>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['stt']) ? $item['stt'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['name_caller']) ? $item['name_caller'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['name_leader']) ? $item['name_leader'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['total']) ? $item['total'] : 0}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_complete']) ? $item['total_complete'] : 0}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_cancel']) ? $item['total_cancel'] : 0}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_caller1']) ? $item['total_caller1'] : 0}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_caller2']) ? $item['total_caller2'] : 0}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_caller3']) ? $item['total_caller3'] : 0}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_pending']) ? $item['total_pending'] : 0}}</td>
                </tr>
        @endforeach
    @endif
    </tbody>
</table>