<?php


namespace App\V1\Transformers\FileCloud;

use App\FileCloud;
use League\Fractal\TransformerAbstract;

class FileCloudTransformer extends TransformerAbstract
{
    public function transform(FileCloud $file)
    {
        return [
            'title'    => $file->title,
            'category' => $file->category,
            'shop'     => $file->shop,
            'path'     => $file->url
        ];
    }
}
