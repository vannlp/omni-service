<?php


namespace App;


class WebsiteTheme extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'website_themes';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        "name",
        "theme_data",
        "screenshot",
        "description",
        "theme_color",
        "theme_style",
        "theme_category",
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];
}