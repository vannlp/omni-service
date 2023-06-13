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
               <th colspan="26" style="text-align: center;font-weight: bold;">THỐNG KÊ CÁC SẢN PHẨM ĐƯỢC MƯA NHIỀU NHẤT THEO KHÁCH HÀNG</th>
          </tr>
          <!-- <tr>
               <th colspan="2" style="font-size: 9px">TỪ THÁNG: {{date("m/Y", strtotime($from ?? time()))}}</th>
               <th colspan="2" style="font-size: 9px">ĐẾN THÁNG: {{date("m/Y", strtotime($to ?? time()))}}</th>
          </tr> -->
          <tr class="header-table">
               <th colspan="2" style="text-align: left; font-weight: bold;">Tên sản phẩm</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Mã sản phẩm</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Giá sản phẩm</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Slug</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Loại sản phẩm</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Tax</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Tags</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Mô tả ngắn</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Mô tả</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Mã danh mục</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Đơn vị</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Hình ảnh</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Số lượng</th>
          </tr>
     </thead>
     <tbody>
          @if(!empty($data))
          @foreach($data as $key => $item)
          <tr>
               <td colspan="2" style="text-align: left">{{!empty($item['name']) ? $item['name'] : ''}}</td>
               <td colspan="2" style="text-align: left">{{ !empty($item['code']) ? $item['code'] : ''}}</td>
               <td colspan="2" style="text-align: left">{{!empty($item['price']) ? number_format($item['price']) : ''}}</td>
               <td colspan="2" style="text-align: left">{{ !empty($item['slug']) ? $item['slug'] : ''}}</td>
               <td colspan="2" style="text-align: left">{{ !empty($item['type']) ? $item['type'] : ''}}</td>
               <td colspan="2" style="text-align: left">{{ !empty($item['tax']) ? $item['tax'] : ''}}</td>
               <td colspan="2" style="text-align: left">{{ !empty($item['tags']) ? $item['tags'] : ''}}</td>
               <td colspan="2" style="text-align: left">{{ !empty($item['short_description']) ? $item['short_description'] : ''}}</td>
               <td colspan="2" style="text-align: left">{{ !empty($item['description']) ? $item['description'] : ''}}</td>
               <td colspan="2" style="text-align: left">{{ !empty($item['category_ids']) ? $item['category_ids'] : ''}}</td>
               <td colspan="2" style="text-align: left">{{ !empty($item['sku']) ? $item['sku'] : ''}}</td>
               <td colspan="2" style="text-align: left">{{ !empty($item['image']) ? $item['image'] : ''}}</td>
               <td colspan="2" style="text-align: left">{{ !empty($item['qty']) ? $item['qty'] : ''}}</td>
          </tr>
          @endforeach
          @endif
     </tbody>
</table>