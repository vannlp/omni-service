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
               <th colspan="10" style="text-align: center; font-size: 18px;font-weight:bold"><b>DANH SÁCH NHÀ PHÂN PHỐI</b></th>
          </tr>
          <tr class="header-table">
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:5px">STT</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Mã NPP</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Tên NPP</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Mã tỉnh/tp</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Tên tỉnh/tp</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Mã quận/huyện</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Tên quận/huyện</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Mã phường/xã</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Tên phường/xã</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Trạng thái</th>
          </tr>
     </thead>
     <tbody>

          @if(!empty($data))
          @php $i = 1 @endphp
          @foreach($data as $key => $item)
          <tr>
               <td style="border: 1px solid #777; text-align: right;width:5px">
                    {{$i++}}
               </td>
               <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{!empty($item['name']) ? $item['name'] : ''}}
               </td>
               <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{!empty($item['code']) ? $item['code'] : ''}}
               </td>
               <td style="border: 1px solid #777; text-align: right;width:15px">
                    {{!empty($item['city_code']) ? $item['city_code'] : ''}}
               </td>
               <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{!empty($item['city_full_name']) ? $item['city_full_name'] : ''}}
               </td>
               <td style="border: 1px solid #777; text-align: right;width:15px">
                    {{!empty($item['district_code']) ? $item['district_code'] : ''}}
               </td>
               <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{!empty($item['district_full_name']) ? $item['district_full_name'] : ''}}
               </td>
               <td style="border: 1px solid #777; text-align: right;width:15px">
                    {{!empty($item['ward_code']) ? $item['ward_code'] : ''}}
               </td>
               <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{!empty($item['ward_full_name']) ? $item['ward_full_name'] : ''}}
               </td>
               <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{ $item['is_active']== 1 ? "Hoạt động" : "Không hoạt động"}}
               </td>
          </tr>
          @endforeach
          @endif
     </tbody>
</table>