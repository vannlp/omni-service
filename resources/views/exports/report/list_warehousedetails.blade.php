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
               <th colspan="12" style="text-align: center;font-weight: bold;">Danh sách tồn kho</th>
          </tr>
          <!-- <tr>
               {{-- <th colspan="2" style="font-size: 9px">TỪ THÁNG: {{date("d/m/Y", strtotime($from ?? time()))}}</th>--}}
               {{-- <th colspan="2" style="font-size: 9px">ĐẾN THÁNG: {{date("d/m/Y", strtotime($to ?? time()))}}</th>--}}
          </tr> -->
          <tr class="header-table">
               <th colspan="2" style="text-align: left; font-weight: bold;">ID sản phẩm</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Mã sản phẩm</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Tên sản phẩm</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Mã kho</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Tên kho</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Đơn vị tính</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Mã lô</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Tên lô</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Số lượng</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Hạn sử dụng</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Biến thể</th>    
          </tr>
     </thead>
     <tbody>
          @if(!empty($data))
          @foreach($data as $key => $item)
        <tr>
            <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['product_id']) ? $item['product_id'] : ''}}</td>
            <td colspan="2" style="border: 1px solid #777; text-align: left">{{!empty($item['product_code']) ? $item['product_code'] : ''}}</td>
            <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['product_name']) ? $item['product_name'] : ''}}</td>
            <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['warehouse_code']) ? $item['warehouse_code'] : ''}}</td>
            <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['warehouse_name']) ? $item['warehouse_name'] : ''}}</td>
            <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['unit_name']) ? $item['unit_name'] : ''}}</td>
            <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['batch_code']) ? $item['batch_code'] : ''}}</td>
            <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['batch_name']) ? $item['batch_name'] : ''}}</td>
            <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['quantity']) ? $item['quantity'] : ''}}</td>
            <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['exp']) ? $item['exp'] : ''}}</td>
            <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['variant_id']) ? $item['variant_id'] : ''}}</td>
        </tr>
          @endforeach
          @endif
     </tbody>
</table>