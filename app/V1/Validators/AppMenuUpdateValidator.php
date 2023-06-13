<?php


namespace App\V1\Validators;


use App\AppMenu;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Http\Request;

class AppMenuUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'       => 'required|exists:app_menus,id,deleted_at,NULL',
            'code'     => 'required',
            'name'     => 'nullable|max:100',
            'store_id' => 'required|exists:stores,id,deleted_at,NULL'
        ];
    }

    protected function attributes()
    {
        return [
            'code'     => Message::get("code"),
            'name'     => Message::get("name"),
            'store_id' => Message::get("stores")
        ];
    }
}