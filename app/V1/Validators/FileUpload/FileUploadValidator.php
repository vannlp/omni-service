<?php


namespace App\V1\Validators\FileUpload;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class FileUploadValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'   => 'required',
            'name' => 'required',
            'size' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'id'   => 'id_file',
            'name' => 'name_file',
            'size' => 'size_file',
        ];
    }
}