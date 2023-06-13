<?php


namespace App\V1\Models;


use App\WebsiteTheme;

class WebsiteThemeModel extends AbstractModel
{
    /**
     * WebsiteModel constructor.
     * @param WebsiteTheme|null $model
     */
    public function __construct(WebsiteTheme $model = null)
    {
        parent::__construct($model);
    }
}