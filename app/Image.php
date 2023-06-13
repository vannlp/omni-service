<?php
/**
 * User: kpistech2
 * Date: 2019-01-21
 * Time: 23:59
 */

namespace App;


use App\V1\Models\ImageModel;

class Image extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'images';

    protected $fillable = [
        "code",
        "url",
        "description",
        "is_active",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public static function uploadImage($base64)
    {
        $date = date('Y-m-d', time());
        $dates = explode("-", $date);
        $year = $dates[0];
        $month = $dates[1];
        $day = $dates[2];
        $path = storage_path('uploads');

        // 1. Upload
        if (!file_exists("$path/$year/$month/$day")) {
            mkdir("$path/$year/$month/$day", 0777, true);
        }
        $imgData = base64_decode($base64);
        $fileName = strtoupper(uniqid()) . ".png";
        $filePath = "$path/$year/$month/$day/$fileName";
        file_put_contents($filePath, $imgData);

        // 2. Save to Image
        $image = new Image();
        $image->url = "$year/$month/$day/$fileName";
        $image->code = $fileName;
        $image->save();

        return $image;
    }

    public static function removeImage($code)
    {
        $image = self::model()->where('code', $code)->first();
        if (empty($image)) {
            return true;
        }

        $path = storage_path('uploads');

        // 1. Remove File
        if (file_exists("$path/{$image->url}")) {
            unlink("$path/{$image->url}");
        }

        // 2. Delete from Image table
        self::model()->where('code', $code)->delete();

        return true;
    }
}
