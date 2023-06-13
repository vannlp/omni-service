<?php


namespace App\V1\Models;


use App\BlogCategory;
use App\Supports\Message;
use App\TM;

class BlogCategoryModel extends AbstractModel
{
    public function __construct(BlogCategory $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $blogCategory = BlogCategory::find($id);
            if (empty($blogCategory)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $blogCategory->name = array_get($input, 'name', $blogCategory->name);
            $blogCategory->slug = !empty($input['name']) ? $this->convert_vi_to_en_to_slug($input['name']) : $blogCategory->slug;
            $blogCategory->blog_id = array_get($input, 'blog_id', $blogCategory->blog_id);
            $blogCategory->website_id = array_get($input, 'website_id', $blogCategory->website_id);
            $blogCategory->is_active = array_get($input, 'is_active', 1);
            $blogCategory->save();
        } else {
            $param = [
                'name'       => $input['name'],
                'slug'       => $this->convert_vi_to_en_to_slug($input['name']),
                'blog_id'    => $input['blog_id'],
                'website_id' => $input['website_id'],
                'is_active'  => array_get($input, 'is_active', 1)
            ];
            $blogCategory = $this->create($param);
        }
        return $blogCategory;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (!empty($input['name'])) {
            $query->where('name', 'like', "%{$input['name']}%");
        }
        if (!empty($input['slug'])) {
            $query->where('slug', $input['slug']);
        }
        if (isset($input['is_active'])) {
            $query->where('is_active', $input['is_active']);
        }
        if (isset($input['website_id'])) {
            $query->where('website_id', $input['website_id']);
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    private function convert_vi_to_en_to_slug($str, $options = [])
    {
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());

        $defaults = [
            'delimiter'     => '-',
            'limit'         => null,
            'lowercase'     => true,
            'replacements'  => [],
            'transliterate' => true,
        ];

        // Merge options
        $options = array_merge($defaults, $options);

        // Lowercase
        if ($options['lowercase']) {
            $str = mb_strtolower($str, 'UTF-8');
        }

        $char_map = [
            // Latin
            'á' => 'a',
            'à' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'ạ' => 'a',
            'ă' => 'a',
            'ắ' => 'a',
            'ằ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            'ặ' => 'a',
            'â' => 'a',
            'ấ' => 'a',
            'ầ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ậ' => 'a',
            'đ' => 'd',
            'é' => 'e',
            'è' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ẹ' => 'e',
            'ê' => 'e',
            'ế' => 'e',
            'ề' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            'ệ' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            'ị' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ọ' => 'o',
            'ô' => 'o',
            'ố' => 'o',
            'ồ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ộ' => 'o',
            'ơ' => 'o',
            'ớ' => 'o',
            'ờ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            'ợ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ụ' => 'u',
            'ư' => 'u',
            'ứ' => 'u',
            'ừ' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            'ự' => 'u',
            'ý' => 'y',
            'ỳ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'ỵ' => 'y',
        ];

        // Make custom replacements
        $str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);

        // Transliterate characters to ASCII
        if ($options['transliterate']) {
            $str = str_replace(array_keys($char_map), $char_map, $str);
        }

        // Replace non-alphanumeric characters with our delimiter
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);

        // Remove duplicate delimiters
        $str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);

        // Truncate slug to max. characters
        $str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');

        // Remove delimiter from ends
        $str = trim($str, $options['delimiter']);

        return $str;
    }
}