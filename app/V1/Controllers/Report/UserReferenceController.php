<?php

namespace App\V1\Controllers\Report;

use App\Supports\Message;
use App\TM;
use App\User;
use App\UserReference;
use App\V1\Controllers\BaseController;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserReferenceController extends BaseController
{
    public function userReferenceByDate(Request $request)
    {
        if (empty($request->input('from'))) {
            throw new HttpException(422, Message::get("V001", Message::get('from')));
        }
        if (empty($request->input('to'))) {
            throw new HttpException(422, Message::get("V001", Message::get('to')));
        }

        $userReferences = UserReference::with([
            'grandChildren', 'userWithTotalSales'
        ])
            ->where('store_id', TM::getCurrentStoreId())
            ->whereBetween('created_at', [date('Y-m-d', strtotime($request->input('from'))), date('Y-m-d', strtotime($request->input('to')))])
            ->whereIn('level', [1, 2])
            ->get();

        $data = [
            'from'           => $request->input('from'),
            'to'             => $request->input('to'),
            'userReferences' => $userReferences
        ];

        $fileName = 'UserReferenceByDate_' . date('YmdHis', time());

        header('Access-Control-Allow-Origin: *');
        \Excel::create($fileName, function ($writer) use ($data) {
            $writer->sheet('Report', function ($sheet) use ($data) {
                $sheet->loadView('exports/report/user_reference_by_date', $data);
            });
        })->export('xlsx');
    }

    public function reportUserReferenceByUser(Request $request)
    {
        $user = User::find($request->input('user_id'));

        if (empty($user)) {
            throw new HttpException(422, Message::get("V001", Message::get('user_id')));
        }

        $userReference = UserReference::with([
            'grandChildrenWithSales'
        ])->where('user_id', $request->input('user_id'))
            ->first();

        $data = [
            'user'          => $user,
            'userReference' => $userReference
        ];

        $fileName = 'UserReferenceByUser_' . date('YmdHis', time());

        header('Access-Control-Allow-Origin: *');
        \Excel::create($fileName, function ($writer) use ($data) {
            $writer->sheet('Report', function ($sheet) use ($data) {
                $sheet->loadView('exports/report/user_reference_by_user', $data);
            });
        })->export('xlsx');
    }

    public function reportUserReferenceByUserAndDate(Request $request)
    {
        $user = User::find($request->input('user_id'));
        if (empty($user)) {
            throw new HttpException(422, Message::get("V001", Message::get('user_id')));
        }
        if (empty($request->input('from'))) {
            throw new HttpException(422, Message::get("V001", Message::get('from')));
        }
        if (empty($request->input('to'))) {
            throw new HttpException(422, Message::get("V001", Message::get('to')));
        }

        $request->merge(['sales_by_date' => true]);

        $userReference = UserReference::with([
            'grandChildrenWithSales'
        ])->where('user_id', $request->input('user_id'))->first();

        $data = [
            'from'          => $request->input('from'),
            'to'            => $request->input('to'),
            'user'          => $user,
            'userReference' => $userReference
        ];

        $fileName = 'UserReferenceByUserAndDate_' . date('YmdHis', time());

        header('Access-Control-Allow-Origin: *');
        \Excel::create($fileName, function ($writer) use ($data) {
            $writer->sheet('Report', function ($sheet) use ($data) {
                $sheet->loadView('exports/report/user_reference_by_user_and_date', $data);
            });
        })->export('xlsx');

    }
}