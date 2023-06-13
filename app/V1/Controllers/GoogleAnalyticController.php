<?php
/*Docs: https://github.com/spatie/laravel-analytics*/

namespace App\V1\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Analytics\AnalyticsFacade;
use Spatie\Analytics\Period;


class GoogleAnalyticController extends BaseController
{

    public function getData(Request $request)
    {
        $query = $request->get('query');
        switch ($query) {
            case 'pages':
                // Lấy các trang được truy cập nhiều nhất trong ngày
                $result = AnalyticsFacade::fetchMostVisitedPages(Period::days(1));
                break;
            case 'visitors':
                //Truy xuất dữ liệu khách truy cập và số lần xem trang trong 15 ngày
                $result = AnalyticsFacade::fetchVisitorsAndPageViews(Period::days(15));
                break;
            case 'total_visitors':
                // Truy xuất dữ liệu lấy tổng số khách truy cập và số lần xem trang
                $result = AnalyticsFacade::fetchTotalVisitorsAndPageViews(Period::days(7));
                break;
            case 'top_referrers':
                // Truy xuất các liên kết giới thiệu hàng đầu
                $result = AnalyticsFacade::fetchTopReferrers(Period::days(7));
                break;
            case 'user_types':
                // Truy xuất loại người dùng
                $result = AnalyticsFacade::fetchUserTypes(Period::days(7));
                break;
            case 'top_browser':
                // Truy xuất các trình duyệt hàng đầu
                $result = AnalyticsFacade::fetchTopBrowsers(Period::days(7));
                break;
            default:
                $result = AnalyticsFacade::performQuery(
                    Period::years(1),
                    'ga:sessions',
                    [
                        'metrics'    => 'ga:sessions, ga:pageviews',
                        'dimensions' => 'ga:yearMonth'
                    ]
                );
        }
        return json_encode($result);
    }
}