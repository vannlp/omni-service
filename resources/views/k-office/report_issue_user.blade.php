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
        <th colspan="6" style="text-align: left;">BÁO CÁO THỜI GIAN LÀM VIỆC
            THÁNG {{date("m", strtotime($from))}}</th>
    </tr>
    <tr>
        <th colspan="6" style="text-align: left;">Tên nhân viên: {{$user}}</th>
    </tr>
    <tr>
        <th></th>
        <th>TỪ NGÀY</th>
        <th>{{date("d/m/Y", strtotime($from))}}</th>
        <th>ĐẾN NGÀY</th>
        <th>{{date("d/m/Y", strtotime($to))}}</th>
    </tr>
    <tr class="header-table">
        <th colspan="6" style="text-align: left;">THÔNG TIN CHI TIẾT</th>
    <tr class="header-table">
        <th>STT</th>
        <th>Vấn đề/ Công việc</th>
        <th>Ngày tạo</th>
        <th>Ngày hoàn thành</th>
        <th>Thời gian xử lý</th>
        <th>Link</th>
        <th style="text-align: left;">Mô tả</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($dataTable))
        @foreach($dataTable as $item)
            <tr>
                @foreach($item as $value)
                    <td style="border: 1px solid #777">{{!empty($value) ? $value : ''}}</td>
                @endforeach
            </tr>
        @endforeach
    @endif
    <tr></tr>
    <tr></tr>
    <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td><strong>Tổng cộng: </strong></td>
        <td style="text-align: center;"><strong> {{$total}}</strong></td>
    </tr>
    </tbody>
</table>