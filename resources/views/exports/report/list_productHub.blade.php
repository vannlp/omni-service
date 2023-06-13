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
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Mã sản phẩm</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Tên sản phẩm</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Đơn vị</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">SL giới hạn/ngày</th>
               <th style="border: 1px solid #777; text-align: center;font-weight:bold;width:15px">Đã bán/ngày</th>

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
               {{-- <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{!empty($item['product_id']) ? $item['product_id'] : ''}}
               </td> --}}
               <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{!empty($item['product_code']) ? $item['product_code'] : ''}}
               </td>
               <td style="border: 1px solid #777; text-align: right;width:15px">
                    {{!empty($item['product_name']) ? $item['product_name'] : ''}}
               </td>
               {{-- <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{!empty($item['unit_id']) ? $item['unit_id'] : ''}}
               </td> --}}
               <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{!empty($item['unit_name']) ? $item['unit_name'] : ''}}
               </td>
               <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{!empty($item['limit_date']) ? $item['limit_date'] : 0}}
               </td>
               <td style="border: 1px solid #777; text-align: left;width:15px">
                    {{!empty($item['qty_re']) ? $item['qty_re'] : 0}}
               </td>

          </tr>
          @endforeach
          @endif
     </tbody>
</table>