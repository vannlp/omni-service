<?php


namespace App\V1\Transformers\File;


use App\File;
use App\Supports\TM_Error;
use App\TM;
use League\Fractal\TransformerAbstract;

class FileTransformer extends TransformerAbstract
{
    public function transform(File $file)
    {
        try {
            return [
                'id'         => $file->id,
                'code'       => $file->code,
                'file_name'  => $file->file_name,
                'title'      => $file->title,
                'file'       => env('UPLOAD_URL') . '/file/' . $file->code,
                'extension'  => $file->extension,
                'type'       => $file->type,
                'size'       => $file->size . ' ' . 'byte',
                'company_id' => TM::getCurrentCompanyId(),
                'created_at' => date('d/m/Y H:i', strtotime($file->created_at)),
                'created_by' => object_get($file, 'createdBy.profile.full_name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}