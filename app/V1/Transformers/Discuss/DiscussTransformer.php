<?php


namespace App\V1\Transformers\Discuss;


use App\Discuss;
use App\File;
use App\Folder;
use App\Profile;
use App\Supports\TM_Error;
use App\TM;
use League\Fractal\TransformerAbstract;

class DiscussTransformer extends TransformerAbstract
{
    public function transform(Discuss $discuss)
    {
        $detail = Discuss::model()->where('parent_id', $discuss->id)->get()->toArray();
        $profile = Profile::model()->where('user_id', $discuss->created_by)->first();
        $data = [];
        foreach ($detail as $value) {
            $profile = Profile::model()->where('user_id', $value['created_by'])->first();
            $avatar = !empty($profile->avatar) ? url('/v0') . "/img/" . $profile->avatar : null;
            $data[] = [
                'id'          => $value['id'],
                'issue_id'    => $value['issue_id'],
                'description' => $value['description'],
                'is_active'   => $value['is_active'],
                'created_by'  => $profile->full_name,
                'avatar'      => $avatar,
                'user_id'     => $value['created_by'],
                'parent_id'   => $value['parent_id'],
                'created_at'  => date('d/m/Y H:i', strtotime($value['created_at'])),
                'updated_at'  => date('d/m/Y H:i', strtotime($value['updated_at'])),
            ];
        }
        $avatar = !empty($profile->avatar) ? url('/v0') . "/img/" . $profile->avatar : null;
        try {
            return [
                'id'          => $discuss->id,
                'issue_id'    => $discuss->issue_id,
                'description' => $discuss->description,
                'image_id'    => $discuss->image_id,
                'file'       => $this->stringToImages($discuss->image_id),
                'is_active'   => $discuss->is_active,
                'created_by'  => object_get($discuss, 'createdBy.profile.full_name'),
                'avatar'      => $avatar,
                'user_id'     => TM::getCurrentUserId(),
                'parent_id'   => $discuss->parent_id,
                'detail'      => empty($data) ? null : $data,
                'created_at'  => date('d/m/Y H:i', strtotime($discuss->created_at)),
                'updated_at'  => date('d/m/Y H:i', strtotime($discuss->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }

    private function stringToImages($id)
    {
        if (empty($id)) {
            return [];
        }
        $images = File::model()->select(['file_name', 'folder_id'])->where('id', $id)->get();
        foreach ($images as $item) {
            $folder_path = Folder::find($item->folder_id);
            if (!empty($folder_path)) {
                $folder_path = str_replace("/", ",", $folder_path->folder_path);
            } else {
                $folder_path = "uploads";
            }
            $folder_path = url('/v0') . "/img/" . $folder_path;
            $file_name = $item->file_name;
            $file = !empty($file_name) ? $folder_path . ',' . $file_name : null;
        }
        return $file;
    }
}