<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        thead tr th {
            vertical-align: middle;
        }

        .header-table th {
            text-align: center;
            border: 1px solid #777;
        }
    </style>
</head>
<body>
<table>
    <thead>
    <tr>
        <th colspan="7" style="text-align: center; font-size: 20px;" height="30">
            {{$reportName ?? "BÁO CÁO"}}
        </th>
    </tr>
    <tr>
        <th height="20"></th>
        <th style="text-align: right"><strong>TỪ NGÀY</strong></th>
        <th>{{isset($from) ? date("d/m/Y", strtotime($from)) : null}}</th>
        <th style="text-align: right"><strong>ĐẾN NGÀY</strong></th>
        <th>{{isset($to) ? date("d/m/Y", strtotime($to)) : null}}</th>
        <th style="text-align: right"><strong>NGÀY XUẤT BÁO CÁO</strong></th>
        <th>{{date("d/m/Y", strtotime($time ?? time()))}}</th>
    </tr>
    @if(!empty($dataHeader))
        <tr class="header-table">
            @foreach($dataHeader as $item)
                <th style="text-align: center" @if(!empty($item['width'])) width="{{$item['width']}}" @endif>
                    @if(!empty($item['label_html']))
                        {!! $item['label_html'] !!}
                    @else
                        {{$item['label']}}
                    @endif
                </th>
            @endforeach
        </tr>
    @endif
    </thead>
    <tbody>
    @if(!empty($dataBody))
        @foreach($dataBody as $i=> $item) @php //print_r($dataBody);die; @endphp
        <tr>
            @foreach($item as $value)
                @if(is_array($value))
                    @if(!empty($value['colspan']))
                        <td colspan="{{$value['colspan']}}">{{$value['value']}}</td>
                    @endif
                @else
                    @php
                        $value = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u','',$value);
                    @endphp
                    <td style="border: 1px solid #777">{{ $value }}</td>
                @endif
            @endforeach
        </tr>
        @endforeach
    @endif
    </tbody>
</table>
</body>
</html>