<?php


namespace App\V1\Validators\WebsiteTheme;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class WebsiteThemeUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'         => 'required|exists:website_themes,id,deleted_at,NULL',
            'name'       => 'required|unique_update_delete:website_themes',
            'theme_data' => 'nullable',
        ];
    }

    protected function attributes()
    {
        return [
            'name'       => Message::get("name"),
            'theme_data' => Message::get("theme_data"),
        ];
    }
}