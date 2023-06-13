<?php


namespace App\V1\Validators\WebsiteTheme;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class WebsiteThemeCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'name'       => 'required|unique_create_delete:website_themes',
            'theme_data' => 'required',
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