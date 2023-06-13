<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <style>
        th {
            text-align: center;
        }
        thead tr th {
            vertical-align: middle;
            border: 5px solid #777;
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
                <th colspan="7" style="text-align: center; font-size: 20px;color:white;background-color:#006e43"
                    height="30">
                    {{ $reportName ?? 'BÁO CÁO' }}
                </th>
            </tr>
            @if (isset($from) && isset($to))
                <tr>
                    <th
                        style="text-align: right;border: 5px solid #777;color:white;background-color: #03205e;font-style:italic">
                        <strong>TỪ:</strong></th>
                    <th style="text-align: left;"><strong>{{date("d/m/Y", strtotime($from))}}</strong></th>
                    <th
                        style="text-align: right;border: 5px solid #777;color:white;background-color:#03205e;font-style:italic">
                        <strong>ĐẾN:</strong></th>
                    <th style="text-align: left;"><strong>{{date("d/m/Y", strtotime($to))}}</strong></th>
                </tr>
            @endif
            @if (!empty($dataHeader))
                <tr class="header-table">
                    @foreach ($dataHeader as $item)
                        <th style="text-align: center;background-color:#ffcd00;border: 5px solid #777;vertical-align: middle;" rowspan="2"
                            @if (!empty($item['width'])) width="{{ $item['width'] }}" @endif>
                            @if (!empty($item['label_html']))
                                {!! $item['label_html'] !!}
                            @else
                                {{ $item['label'] }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            @endif
        </thead>
        <tbody>
            <tr>
                <td></td>
            </tr>
            @if (!empty($dataBody))
                @foreach ($dataBody as $i => $item)
                    @php
                        //print_r($dataBody);die;
                    @endphp
                    <tr>
                        @foreach ($item as $value)
                            @if (is_array($value))
                                @if (!empty($value['colspan']))
                                    <td colspan="{{ $value['colspan'] }}">{{ $value['value'] }}</td>
                                @endif
                            @else
                                @php
                                    $value = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $value);
                                @endphp
                                <td style="border: 1px solid #777;wrap-text:true;">{!! nl2br(e($value)) !!}</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach

            @endif
        </tbody>
    </table>
</body>

</html>
