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
        <th colspan="12" style="text-align: center;font-weight: bold;">BÁO CÁO TÍCH ĐIỂM THÀNH VIÊN</th>
    </tr>
    <tr>
        <th style="font-size: 9px">Tên thành viên: </th>
        <th style="font-size: 9px">{{ $userCode ?? "" }}</th>
    </tr>
    <tr>
        <th style="font-size: 9px">Nhóm thành viên: </th>
        <th style="font-size: 9px">{{ $groupCode ?? "" }}</th>
    </tr>
    <tr class="header-table">
        <th style="text-align: left; font-weight: bold;">Mã thành viên</th>
        <th style="text-align: left; font-weight: bold;">Tên thành viên</th>
        <th style="text-align: left; font-weight: bold;">Điểm tích lũy</th>
        <th style="text-align: left; font-weight: bold;">Tên hạng</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['userCode']) ? str_replace(['\b','?',''], '', $item['userCode']) : ''}}</td>
                <td style="border: 1px solid #777; text-align: right">{{!empty($item['userName']) ? str_replace(['\b','?',''], '', $item['userName']) : ''}}</td>
                <td style="border: 1px solid #777; text-align: right">{{!empty($item['customerPoint']) ? $item['customerPoint'] : 0}}</td>
                <td style="border: 1px solid #777; text-align: right">{{!empty($item['rankName']) ? $item['rankName'] : ''}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>