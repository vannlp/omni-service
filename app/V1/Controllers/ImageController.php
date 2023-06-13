<?php
/**
 * User: kpistech2
 * Date: 2019-01-21
 * Time: 23:31
 */

namespace App\V1\Controllers;


use App\Image;

class ImageController extends BaseController
{

    protected $model;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * @param $code
     * @return \Illuminate\Http\Response
     */
    public function index($code)
    {
        $image = \App\Image::model()->where('code', $code)->first();
        if (empty($image)) {
            abort(404);
        }

        $path = storage_path('uploads/' . $image->url);
        return Image::make($path)->response();
    }
}