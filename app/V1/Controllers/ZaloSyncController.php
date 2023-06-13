<?php

namespace App\V1\Controllers;

use App\Store;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\CategoryModel;
use App\V1\Models\CategoryStoreModel;
use App\V1\Models\FileModel;
use App\V1\Models\OrderDetailModel;
use App\V1\Models\OrderModel;
use App\V1\Models\ProductModel;
use App\V1\Models\SyncLogModel;
use App\V1\Models\UserModel;
use App\V1\Models\ZaloModel;
use App\V1\Transformers\Zalo\ZaloSyncLogTransformer;
use App\ZaloStoreCategory;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZaloSyncController extends BaseController
{
    const MESSAGE_SUCCESS = 'Success';
    const STATUS_SHOW = 'show';
    const INDUSTRY = 164;

    protected $model;
    protected $product;
    protected $order;
    protected $orderDtl;
    protected $user;
    protected $category;
    protected $categoryStore;
    protected $syncLog;
    protected $file;
    protected $client;
    protected $token;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->model = new ZaloModel();
        $this->product = new ProductModel();
        $this->order = new OrderModel();
        $this->orderDtl = new OrderDetailModel();
        $this->user = new UserModel();
        $this->category = new CategoryModel();
        $this->categoryStore = new CategoryStoreModel();
        $this->syncLog = new SyncLogModel();
        $this->file = new FileModel();
        $this->client = new Client();
    }

    private function getToken($storeId)
    {
        $zalo = $this->model->getFirstBy('store_id', $storeId);
        if (!$zalo) {
            return;
        }
//        if (!$zalo) {
//            return $this->response->errorBadRequest(Message::get("V044", "ID #$storeId"));
//        }
        $this->token = $zalo->zalo_access_token;
    }

    public function syncProduct($storeId)
    {
        $total = 0;

        try {
            DB::beginTransaction();

            $store = Store::findOrFail($storeId);
            $this->getToken($store->id);

            $products = $this->product->findWhere([
                'store_id'  => $storeId,
                'sync_zalo' => 0,
            ]);

            foreach ($products as $product) {
                if (!$product->thumbnail) {
                    $logParams = [
                        'issue'    => sprintf('Sản phẩm [#%s] không có hình ảnh đại diện.', $product->id),
                        'type'     => TYPE_SYNC_PRODUCT,
                        'store_id' => $storeId,
                        'from'     => OMNI_CHANEL_CODE[OMNI_CHANEL_ZALO],
                    ];
                    $this->syncLog->refreshModel();
                    $this->syncLog->create($logParams);
                    continue;
//                    return $this->response->errorBadRequest(Message::get("V049", "ID #$product->id"));
                }

                if ($product->category_ids) {
                    $categoryIds = $this->handleCreateCategory($product->category_ids, $storeId);
                }

                $photos = [];
                $thumbnail = $this->uploadImageZalo($product->file->url);
                if (isset($thumbnail['error'])) {
                    $logParams = [
                        'issue'    => sprintf('Sản phẩm [#%s]: %s', $product->id, $thumbnail['message']),
                        'type'     => TYPE_SYNC_PRODUCT,
                        'store_id' => $storeId,
                        'from'     => OMNI_CHANEL_CODE[OMNI_CHANEL_ZALO],
                    ];
                    $this->syncLog->refreshModel();
                    $this->syncLog->create($logParams);
                    continue;
                }
                $photos[] = $thumbnail;
                if ($product->gallery_images) {
                    foreach (explode(',', $product->gallery_images) as $item) {
                        $file = $this->file->getFirstBy('id', $item);
                        if (true) {
                            $logParams = [
                                'issue'    => sprintf('File [#%s] không tồn tại.', $item),
                                'type'     => TYPE_SYNC_PRODUCT,
                                'store_id' => $storeId,
                                'from'     => OMNI_CHANEL_CODE[OMNI_CHANEL_ZALO],
                            ];
                            $this->syncLog->refreshModel();
                            $this->syncLog->create($logParams);
                            continue;
//                            return $this->response->errorBadRequest(Message::get("V049", "ID #$product->id"));
                        }
                        $image = $this->uploadImageZalo($file->url);
                        if (isset($image['error'])) {
                            $logParams = [
                                'issue'    => sprintf('File [#%s]: %s', $item, $image['message']),
                                'type'     => TYPE_SYNC_PRODUCT,
                                'store_id' => $storeId,
                                'from'     => OMNI_CHANEL_CODE[OMNI_CHANEL_ZALO],
                            ];
                            $this->syncLog->refreshModel();
                            $this->syncLog->create($logParams);
                            continue;
                        }
                        $photos[] = $image;
                    }
                }

                $params = [
                    'code'         => $product['code'],
                    'name'         => $product['name'],
                    "industry"     => self::INDUSTRY,
                    'price'        => $product['price'],
                    'description'  => $product['description'],
                    "status"       => self::STATUS_SHOW,
                    'photos'       => $photos,
                    'categories'   => $categoryIds,
                    'package_size' => [
                        'weight' => $product['weight'] ?? 1,
                        'length' => $product['length'] ?? 1,
                        'width'  => $product['width'] ?? 1,
                        'height' => $product['height'] ?? 1,
                    ],
                ];

                $isSyncProduct = $this->createProductZalo($params);

                if (isset($isSyncProduct['error'])) {
                    $logParams = [
                        'issue'    => sprintf('Sản phẩm [#%s]: %s', $product->id, $isSyncProduct['message']),
                        'type'     => TYPE_SYNC_PRODUCT,
                        'store_id' => $storeId,
                        'from'     => OMNI_CHANEL_CODE[OMNI_CHANEL_ZALO],
                    ];
                    $this->syncLog->refreshModel();
                    $this->syncLog->create($logParams);
                } else {
                    $total++;
                    $product->sync_zalo = 1;
                    $product->save();
                    Log::update($this->product->getTable(), "#ID:" . $product->id);
                }
            }

            DB::commit();

            return ['status' => Message::get("zalo.sync-product-success", $total)];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function uploadImageZalo($url)
    {
        try {
            $info = pathinfo($url);
            $contents = file_get_contents($url);

            $url = env('ZALO_UPLOAD_IMAGE') . '?access_token=' . $this->token . "&upload_type=product";

            $response = $this->client->request('post', $url, [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => $contents,
                        'filename' => $info['basename'],
                    ],
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['message'] == self::MESSAGE_SUCCESS) {
                return $result['data']['id'];
            } else {
                return $result;
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function createProductZalo($params)
    {
        try {
            $url = env('ZALO_SYNC_PRODUCTS_URL') . '?access_token=' . $this->token;
            $response = $this->client->request('post', $url, [
                'json' => $params,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['message'] == self::MESSAGE_SUCCESS) {
                return true;
            }

            return $result;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function handleCreateCategory($ids, $storeId)
    {
        try {
            $result = [];

            foreach (explode(',', $ids) as $item) {
                $this->category->refreshModel();
                $category = $this->category->getFirstBy('id', $item);
                if (!$category) {
                    $logParams = [
                        'issue'    => sprintf('Danh mục [#%s] không tồn tại.', $item),
                        'type'     => TYPE_SYNC_PRODUCT,
                        'store_id' => $storeId,
                        'from'     => OMNI_CHANEL_CODE[OMNI_CHANEL_ZALO],
                    ];
                    $this->syncLog->refreshModel();
                    $this->syncLog->create($logParams);
                    continue;
//                    return $this->response->errorBadRequest(Message::get("V047", "#$item"));
                }

                if ($category && !$category->image_id) {
                    $logParams = [
                        'issue'    => sprintf('Danh mục [#%s] không tồn tại hoặc không có hình ảnh.', $item),
                        'type'     => TYPE_SYNC_PRODUCT,
                        'store_id' => $storeId,
                        'from'     => OMNI_CHANEL_CODE[OMNI_CHANEL_ZALO],
                    ];
                    $this->syncLog->refreshModel();
                    $this->syncLog->create($logParams);
                    continue;
//                    return $this->response->errorBadRequest(Message::get("V050", "#$item"));
                }

                $this->categoryStore->refreshModel();
                $isCategoryStore = $this->categoryStore->getFirstWhere([
                    'category_id' => $item,
                    'store_id'    => $storeId
                ]);
                if (!$isCategoryStore) {
                    $logParams = [
                        'issue'    => sprintf('Danh mục [#%s] không thuộc cửa hàng [#%s].', $item, $storeId),
                        'type'     => TYPE_SYNC_PRODUCT,
                        'store_id' => $storeId,
                        'from'     => OMNI_CHANEL_CODE[OMNI_CHANEL_ZALO],
                    ];
                    $this->syncLog->refreshModel();
                    $this->syncLog->create($logParams);
                    continue;
//                    return $this->response->errorBadRequest(Message::get("V048", "#$item", "#$storeId"));
                }

                $zaloStoreCategory = $isCategoryStore->zaloStoreCategory ?? new ZaloStoreCategory();
                if (empty($zaloStoreCategory->sync_zalo) || $zaloStoreCategory->sync_zalo == 0) {
                    $photo = $this->uploadImageZalo($isCategoryStore->category->file->url);
                    if (isset($photo['error'])) {
                        $logParams = [
                            'issue'    => sprintf('Danh mục [#%s]: %s', $item, $photo['message']),
                            'type'     => TYPE_SYNC_PRODUCT,
                            'store_id' => $storeId,
                            'from'     => OMNI_CHANEL_CODE[OMNI_CHANEL_ZALO],
                        ];
                        $this->syncLog->refreshModel();
                        $this->syncLog->create($logParams);
                        continue;
                    }
                    $params = [
                        "name"        => $category->name,
                        "photo"       => $photo,
                        "description" => $category->description,
                        "status"      => self::STATUS_SHOW,
                    ];
                    $categoryId = $this->createCategoryZalo($params);
                    $zaloStoreCategory->zalo_category_id = $categoryId;
                    $zaloStoreCategory->sync_zalo = 1;
                    $zaloStoreCategory->category_store_id = $isCategoryStore->id;
                    $zaloStoreCategory->save();
                }

                $result[] = $zaloStoreCategory->zalo_category_id;
            }

            return $result;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function createCategoryZalo($params)
    {
        try {
            $url = env('ZALO_CREATE_CATEGORY_URL') . '?access_token=' . $this->token;
            $response = $this->client->request('post', $url, [
                'json' => $params,
            ]);

            $data = $response->getBody()->getContents();

            $result = !empty($data) ? json_decode($data, true) : [];

            if ($result['message'] == self::MESSAGE_SUCCESS) {
                return $result['data']['id'];
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function syncOrder($storeId)
    {
        try {
            $store = Store::findOrFail($storeId);
            $this->getToken($store->id);

            $orders = $this->getListOrders();

            DB::beginTransaction();

            foreach ($orders as $item) {
                $isOrder = $this->order->getFirstBy('code', $item['code']);
                if (!$isOrder) {
                    $customerId = $this->createCustomer($item['customer']);
                    $order = $this->createOrder($item, $storeId, $customerId);
                    $this->createOrderDtl($item, $order->id);
                }
            }

            DB::commit();

            return ['status' => Message::get("zalo.sync-order-success")];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function getListOrders()
    {
        try {
            $url = env('ZALO_SYNC_ORDER_URL') . '?access_token=' . $this->token;
            $response = $this->client->request('get', $url, []);
            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['message'] == self::MESSAGE_SUCCESS) {
                return $result['data']['orders'] ?? null;
            }

            return $result['data'] ?? [];

        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function createOrder(array $input, $store_id = null, $customerId)
    {
        try {
            $address = $input['customer']['address'] . ', ' . $input['customer']['district_name'] . ', ' . $input['customer']['city_name'];
            $params = [
                'code'             => $input['code'],
                'customer_id'      => $customerId,
                'total_price'      => $input['total_amount'],
                'payment_status'   => !empty($input['payment']['status']) && $input['payment']['status'] == 2 ? 1 : 0,
                'payment_method'   => ZALO_PAYMENT_METHOD[$input['payment']['method']],
                'status'           => ZALO_ORDER_STATUS[$input['status']],
                'note'             => $input['extra_note'],
                'shipping_address' => $address,
                'omni_chanel_code' => OMNI_CHANEL_ZALO,
                'chanel_data_json' => json_encode($input),
                'store_id'         => $store_id,
            ];
            $this->order->refreshModel();
            return $this->order->create($params);

        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function createOrderDtl(array $input, $orderId)
    {
        try {
            foreach ($input['order_items'] as $item) {
                $product = $this->product->getFirstBy('code', $item['code']);
                if ($product) {
                    $params = [
                        'order_id'   => $orderId,
                        'product_id' => $product->id,
                        'qty'        => $item['quantity'],
                        'price'      => $item['price'],
                        'total'      => $item['quantity'] * $item['price'],
                    ];
                    $this->orderDtl->refreshModel();
                    $this->orderDtl->create($params);
                }
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function createCustomer(array $input)
    {
        try {
            $user = $this->user->getFirstWhere(['phone' => $input['phone']]);
            if (!$user) {
                $this->user->refreshModel();
                $user = $this->user->create([
                    'phone'   => $input['phone'],
                    'code'    => $input['phone'],
                    'role_id' => USER_ROLE_GUEST_ID,
                    'type'    => USER_TYPE_CUSTOMER,
                ]);
                $user->profile()->create([
                    'short_name' => $input['name'],
                    'full_name'  => $input['name'],
                    'phone'      => $input['phone'],
                ]);
            }

            return $user->id;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function syncUpdateOrder($orderId)
    {
        try {
            $order = $this->order->getFirstBy('id', $orderId);
            if ($order && $order->omni_chanel_code == OMNI_CHANEL_ZALO) {
                $this->getToken($order->store_id);

                $dataChanel = json_decode($order->chanel_data_json, true);
                $status = $order->status == 'COMMING' ?
                    array_search('INPROGRESS', ZALO_ORDER_STATUS) :
                    array_search($order->status, ZALO_ORDER_STATUS);
                $params = [
                    'id'            => $dataChanel['id'],
                    'status'        => $status,
                    'cancel_reason' => $order->canceled_reason,
                ];
                $result = $this->syncUpdateOrderZalo($params);
                if ($result) {
                    return ['status' => Message::get("zalo.sync-update-order-success")];
                } else {
                    return ['status' => Message::get("zalo.sync-update-order-fail")];
                }
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function syncUpdateOrderZalo(array $params)
    {
        try {
            $url = env('ZALO_SYNC_UPDATE_ORDER_URL') . '?access_token=' . $this->token;
            $response = $this->client->request('post', $url, [
                'json' => $params,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['message'] == self::MESSAGE_SUCCESS) {
                return true;
            }

            return false;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function showLogs(Request $request, ZaloSyncLogTransformer $transformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $logs = $this->syncLog->search($input, [], $limit);
        Log::view($this->syncLog->getTable());
        return $this->response->paginator($logs, $transformer);
    }
}
