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
               <th colspan="6" style="text-align: center;font-weight: bold;">THỐNG KÊ CÁC KHÁCH HÀNG CÓ DOANH SỐ CAO NHẤT</th>
          </tr>
          <!-- <tr>
               <th colspan="2" style="font-size: 9px">TỪ THÁNG: {{date("m/Y", strtotime($from ?? time()))}}</th>
               <th colspan="2" style="font-size: 9px">ĐẾN THÁNG: {{date("m/Y", strtotime($to ?? time()))}}</th>
          </tr> -->
          <tr class="header-table">
               <th colspan="2" style="text-align: center; font-weight: bold;">Khách hàng</th>
               <th colspan="2" style="text-align: center; font-weight: bold;">Tổng số đơn hàng</th>
               <th colspan="2" style="text-align: center; font-weight: bold;">Tổng số tiền đã mua </th>
          </tr>
     </thead>
     <tbody>
          @if(!empty($data))
          @foreach($data as $key => $item)
          <tr>
               <td colspan="2" style="text-align: right">{{!empty($item['customer_name']) ? $item['customer_name'] : ''}}</td>
               <td colspan="2" style="text-align: right">{{ !empty($item['total_order']) ? $item['total_order'] : ''}}</td>
               <td colspan="2" style="text-align: right">{{!empty($item['total_price']) ? number_format($item['total_price']) : ''}}</td>
          </tr>
          @endforeach
          @endif
     </tbody>
</table>