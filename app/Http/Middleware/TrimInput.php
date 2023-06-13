<?php

namespace App\Http\Middleware;

use Closure;

class TrimInput
{
    //public function handle(Request $request, Closure $next)
    //{
    //    // Perform action
    //    $input = $request->all();print_r($input);die;
    //    $input = $request->getContent();
    //    $input = mb_convert_encoding(urldecode($input), "UTF-8");
    //    if ($isJson = json_decode($input, true)) {
    //        $input = $isJson;
    //    } else {
    //        $input = "http://abc.com?$input";
    //        $parts = parse_url($input, PHP_URL_QUERY);
    //        parse_str($parts, $input);
    //    }
    //
    //    $input = $this->trimArray($input);
    //
    //    $request->replace($input);
    //
    //    return $next($request);
    //}

    public function handle($request, Closure $next)
    {
        // Perform action
        if (!$input = $request->all()) {
            $input = json_decode($request->getContent(), true);

            $input = is_array($input) ? $input : [$input];
        }

        $input = $this->trimArray($input);

        $input = array_filter($input, function($value) { return $value !== ''; });

        $request->replace($input);

        return $next($request);
    }

    /**
     * Trims a entire array recursivly.
     *
     * @param array $input
     *
     * @return array
     */
    function trimArray($input)
    {

        if (!is_array($input)) {
            return trim($input);
        }

        return array_map([$this, 'trimArray'], $input);
    }
}