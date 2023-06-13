<?php
$api->post('/social-mapping-user', function (\Illuminate\Http\Request $request) {
    $input = $request->all();
    try {
        \Illuminate\Support\Facades\DB::beginTransaction();
        $time = time();

        if ($input['social_type'] == "FACEBOOK") {
            $typeId = 'fb_id';
        } else {
            $typeId = 'gg_id';
        }

        $user = \App\User::model()->where('id', \App\TM::getCurrentUserId())->first();
        $user->{$typeId} = $input['id'];
        $user->updated_at = date("Y-m-d H:i:s", $time);
        $user->save();
        \Illuminate\Support\Facades\DB::commit();

        return response()->json(['status' => 'User maps to ' . $input['social_type'] . ' successfully']);
    } catch (Exception $ex) {
        \Illuminate\Support\Facades\DB::rollBack();
        return response()->json(['status' => 'error', 'error' => ['errors' => ["msg" => $ex->getMessage()]]], 500);
    }
});