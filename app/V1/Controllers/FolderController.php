<?php


namespace App\V1\Controllers;


use App\File;
use App\Folder;
use App\Supports\TM_Error;
use App\Supports\Log;
use App\Supports\Message;
use App\TM;
use App\V1\Models\FolderModel;
use App\V1\Transformers\Folder\FolderTransformer;
use App\V1\Validators\FolderCreateValidator;
use App\V1\Validators\FolderUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FolderController extends BaseController
{
    public function __construct()
    {
        $this->model = new FolderModel();
    }

    /**
     * @param Request $request
     * @param FolderTransformer $folderTransformer
     * @return \Dingo\Api\Http\Response
     */

    public function search(Request $request, FolderTransformer $folderTransformer)
    {
        $input  = $request->all();
        $folder = Folder::model()->whereNull('parent_id')->where('company_id', TM::getCurrentCompanyId())->orderBy('id', 'DESC');
//        if (!empty($input['store_id'])) {
//            $folder = $folder->where('store_id', $input['store_id']);
//        }
        $folder  = $folder->get();
        $folders = [];
        foreach ($folder as $item) {
            $folders[] = [
                'id'          => $item->id,
                'folder_name' => $item->folder_name,
                'folder_path' => $item->folder_path,
                'folder_key'  => $item->folder_key,
                'parent_id'   => $item->parent_id,
                'company_id'  => $item->company_id,
                //                'store_id'    => $item->store_id,
                'is_active'   => $item->is_active,
                'created_by'  => object_get($item, 'createdBy.profile.full_name'),
                'created_at'  => date('d/m/Y H:i', strtotime($item->created_at)),
                'updated_at'  => date('d/m/Y H:i', strtotime($item->updated_at)),
            ];

        }

        $file = File::model()->whereNull('folder_id')->where('company_id', TM::getCurrentCompanyId())->orderBy('id', 'DESC');
//        if (!empty($input['store_id'])) {
//            $file = $file->where('store_id', $input['store_id']);
//        }
        $file      = $file->get();
        $files     = [];
        $fileModel = new File();
        foreach ($file as $item) {
            $folder_path = object_get($item, 'folder.folder_path');
            if (!empty($folder_path)) {
                $folder_path = str_replace("/", ",", $folder_path);
            } else {
                $folder_path = "uploads";
            }
            $folder_path    = url('/v0') . "/img/" . $folder_path;
            $url            = env('UPLOAD_URL') . '/file/' . $item->code;
            $statusResponse = $fileModel->checkFile($url);
            $fileServer     = $statusResponse == 200 ? $url : null;
            $fileUrl        = !empty($fileServer) ? $fileServer : (!empty($folder_path) ? $folder_path . ',' . $item->file_name : null);
            $files[]        = [
                'id'          => $item->id,
                'code'        => $item->code,
                'file_name'   => $item->file_name,
                'title'       => $item->title,
                'folder_id'   => $item->folder_id,
                'folder_name' => object_get($item, 'folder.folder_name'),
                'folder_path' => object_get($item, 'folder.folder_path'),
                'file'        => $fileUrl,
                'extension'   => $item->extension,
                'size'        => !empty($item->size) ? $item->size . ' ' . 'byte' : null,
                'company_id'  => $item->company_id,
                //                'store_id'    => $item->store_id,
                'is_active'   => $item->is_active,
                'created_at'  => date('d/m/Y H:i', strtotime($item->created_at)),
                'created_by'  => object_get($item, 'createdBy.profile.full_name'),
                'updated_at'  => date('d/m/Y H:i', strtotime($item->updated_at)),
            ];
        }
        // add to the list
        $data ['folders'] = $folders;
        $data ['files']   = $files;
//        $data = [];
//        $data[] = [
//            'folders' => $folders,
//            'files'   => $files,
//        ];

        if (empty($data)) {
            return ['data' => ''];
        }
        Log::view($this->model->getTable());
        return response()->json(['data' => $data]);
    }

    public function detail($id)
    {
        try {
            $result = Folder::find($id);
            Log::view($this->model->getTable());
            if (empty($result)) {
                return ["data" => []];
            } else {
                //------------------------------------- Detail Folder -------------------------------------//
                $folder = Folder::model()->where('parent_id', $id)->where('company_id', TM::getCurrentCompanyId())->orderBy('id', 'DESC');
//                if (!empty($input['store_id'])) {
//                    $folder = $folder->where('store_id', $input['store_id']);
//                }
                $folder  = $folder->get();
                $folders = [];
                foreach ($folder as $item) {
                    $folders[] = [
                        'id'          => $item->id,
                        'folder_name' => $item->folder_name,
                        'folder_path' => $item->folder_path,
                        'folder_key'  => $item->folder_key,
                        'parent_id'   => $item->parent_id,
                        'is_active'   => $item->is_active,
                        'created_by'  => object_get($item, 'createdBy.profile.full_name'),
                        'created_at'  => date('d/m/Y H:i', strtotime($item->created_at)),
                        'updated_at'  => date('d/m/Y H:i', strtotime($item->updated_at)),
                    ];

                }
                $file = File::model()->where('folder_id', $id)->where('company_id', TM::getCurrentCompanyId())->orderBy('id', 'DESC');
//                if (!empty($input['store_id'])) {
//                    $file = $file->where('store_id', $input['store_id']);
//                }
                $file  = $file->get();
                $files = [];
                foreach ($file as $item) {
                    $folder_path = object_get($item, 'folder.folder_path');
                    if (!empty($folder_path)) {
                        $folder_path = str_replace("/", ",", $folder_path);
                    } else {
                        $folder_path = "uploads";
                    }
                    $folder_path = url('/v0') . "/img/" . $folder_path;

                    $files[] = [
                        'id'          => $item->id,
                        'code'        => $item->code,
                        'file_name'   => $item->file_name,
                        'title'       => $item->title,
                        'folder_id'   => $item->folder_id,
                        'folder_name' => object_get($item, 'folder.folder_name'),
                        'folder_path' => object_get($item, 'folder.folder_path'),
                        'file'        => !empty($folder_path) ? $folder_path . ',' . $item->file_name : null,
                        'extension'   => $item->extension,
                        'size'        => !empty($item->size) ? $item->size . ' ' . 'byte' : null,
                        'is_active'   => $item->is_active,
                        'created_at'  => date('d/m/Y H:i', strtotime($item->created_at)),
                        'created_by'  => object_get($item, 'createdBy.profile.full_name'),
                        'updated_at'  => date('d/m/Y H:i', strtotime($item->updated_at)),
                    ];
                }
                // add to the list
                $data ['folders'] = $folders;
                $data ['files']   = $files;

                if (empty($data)) {
                    return ['data' => ''];
                }
                Log::view($this->model->getTable());
                return response()->json(['data' => $data]);

            }
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
    }


    public function create(
        Request $request,
        FolderCreateValidator $folderCreateValidator,
        FolderTransformer $folderTransformer
    )
    {
        $input = $request->all();
        $folderCreateValidator->validate($input);
        /*  if (!empty($input['folder_path'])) {
              $bool = Folder::model()->where('folder_name', $input['folder_name'])
                  ->where('folder_path', $input['folder_path'])->first();
              if (!empty($bool)) {
                  throw new \Exception(Message::get("V008", "folder: #$bool->folder_name"));
              }
          } else {
              $bool = Folder::model()->where('folder_name', $input['folder_name'])->first();
              if (!empty($bool)) {
                  throw new \Exception(Message::get("V008", "folder: #$bool->folder_name"));
              }
          }*/

        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::create($this->model->getTable(), $result->folder_name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("folders.create-success", $result->folder_name)];
    }

    public function update(
        $id,
        Request $request,
        FolderUpdateValidator $folderUpdateValidator,
        FolderTransformer $folderTransformer
    )
    {
        $input       = $request->all();
        $input['id'] = $id;
        $folderUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::update($this->model->getTable(), $result->folder_name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("folders.update-success", $result->folder_name)];
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = Folder::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete Departments
            $result->delete();
            Log::delete($this->model->getTable(), $result->folder_name);
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("folders.delete-success", $result->folder_name)];
    }

    public function moveFolder($id, Request $request)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $result      = $this->model->upsert($input);

        //------------------------------------- Detail Folder -------------------------------------//
        $folder  = Folder::model()->where('parent_id', $id)->orderBy('id', 'DESC')->get();
        $folders = [];
        foreach ($folder as $item) {
            $folders[] = [
                'id'          => $item->id,
                'folder_name' => $item->folder_name,
                'folder_path' => $item->folder_path,
                'folder_key'  => $item->folder_key,
                'parent_id'   => $item->parent_id,
                'is_active'   => $item->is_active,
                'created_by'  => object_get($item, 'createdBy.profile.full_name'),
                'created_at'  => date('d/m/Y H:i', strtotime($item->created_at)),
                'updated_at'  => date('d/m/Y H:i', strtotime($item->updated_at)),
            ];

        }
        $file  = File::model()->where('folder_id', $id)->orderBy('id', 'DESC')->get()->take(20);
        $files = [];
        foreach ($file as $item) {
            $folder_path = object_get($item, 'folder.folder_path');
            if (!empty($folder_path)) {
                $folder_path = str_replace("/", ",", $folder_path);
            } else {
                $folder_path = "uploads";
            }
            $folder_path = url('/v0') . "/img/" . $folder_path;

            $files[] = [
                'id'          => $item->id,
                'code'        => $item->code,
                'file_name'   => $item->file_name,
                'title'       => $item->title,
                'folder_id'   => $item->folder_id,
                'folder_name' => object_get($item, 'folder.folder_name'),
                'folder_path' => object_get($item, 'folder.folder_path'),
                'file'        => !empty($folder_path) ? $folder_path . ',' . $item->file_name : null,
                'extension'   => $item->extension,
                'size'        => !empty($item->size) ? $item->size . ' ' . 'byte' : null,
                'is_active'   => $item->is_active,
                'created_at'  => date('d/m/Y H:i', strtotime($item->created_at)),
                'created_by'  => object_get($item, 'createdBy.profile.full_name'),
                'updated_at'  => date('d/m/Y H:i', strtotime($item->updated_at)),
            ];
        }
        // add to the list
        $data   = [];
        $data[] = [
            'folders' => $folders,
            'files'   => $files,
        ];

        if (empty($data)) {
            return ['data' => ''];
        }
        Log::move($this->model->getTable());
        return response()->json(['data' => $data]);

    }

}