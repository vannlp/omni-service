<?php


namespace App\V1\Models;


use App\Folder;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Facades\File;

class FolderModel extends AbstractModel
{
    public function __construct(Folder $model = null)
    {
        parent::__construct($model);
    }

    public $cate_child;

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;

        if ($id) {
            $folders = Folder::find($id);
            if (empty($folders)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            // $foderNameOld = $folders->folder_name;
            //  $boolFolderNameStrpos = $folders->folder_name . '/';
            $folders->folder_name = array_get($input, 'folder_name', $folders->folder_name);
            $folders->folder_key = array_get($input, 'folder_key', $folders->folder_key);
            $folders->parent_id = array_get($input, 'parent_id', $folders->parent_id);
            $folders->store_id = array_get($input, 'store_id', $folders->store_id);
            $folders->company_id = TM::getCurrentCompanyId();
            $folders->updated_at = date("Y-m-d H:i:s", time());
            $folders->updated_by = TM::getCurrentUserId();
            $folders->save();
        } else {

            $folderName = $input['folder_name'];
            $folderPath = !empty($input['folder_path']) ? $input['folder_path'] . '/' . $input['folder_name'] : 'uploads/' . $input['folder_name'];

            if (!empty($input['folder_path'])) {
                $folder = Folder::model()->where('folder_path', '=', $input['folder_path'])->first();
            }
            $param = [
                'folder_name' => $input['folder_name'],
                'folder_path' => $folderPath,
                'parent_id'   => array_get($input, 'parent_id'),
                'folder_key'  => array_get($input, 'folder_key'),
                'company_id'  => TM::getCurrentCompanyId(),
                'store_id'    => array_get($input, 'store_id'),
                'is_active'   => 1,

            ];
            if (!empty($input['folder_path'])) {
                $path = public_path() . '/' . $input['folder_path'] . '/' . $folderName;
                File::makeDirectory($path, $mode = 0777, true, true);
            } else {
                $path = public_path() . '/uploads/' . '' . $folderName;
                File::makeDirectory($path, $mode = 0777, true, true);
            }

            $folders = $this->create($param);
        }

        return $folders;
    }

    public function childElementList(array $folders, $parentId = null)
    {
        $listFolder = [];
        $allUnset = $folders;
        $allFill = $folders;

        foreach ($allFill as $key => $value) {
            if ($value['id'] == $parentId) {
                continue;
            } else if (empty($value['parent_id'])) {
                unset($allFill[$key]);
            }
        }

        foreach ($folders as $key => $value) {
            if ($value['id'] == $parentId) {
                array_push($listFolder, $value);
                unset($allUnset[$key]);

            } else if ($value['parent_id'] == $parentId) {

                array_push($listFolder, $value);
                unset($allUnset[$key]);
            }
        }

        foreach ($allFill as $key1 => $item1) {
            {
                foreach ($allUnset as $key2 => $item2) {
                    if ($item2['parent_id'] == $item1['id']) {
                        if (in_array($item2, $listFolder)) {
                            continue;
                        }
                        array_push($listFolder, $item2);
                    }
                }
            }
        }
        $this->cate_child = $listFolder;
    }
}