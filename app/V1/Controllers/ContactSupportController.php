<?php


namespace App\V1\Controllers;

use App\Company;
use App\ContactSupport;
use App\Jobs\SendMailContact;
use App\Jobs\SendMailExportForm;
use App\MailContact;
use App\ExportForm;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\ContactSupportModel;
use App\V1\Transformers\ContactSupport\ContactSupportTransformer;
use App\V1\Validators\ContactSupportCreateValidator;
use App\V1\Validators\ContactSupportUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Supports\DataUser;
use App\TM;
use App\User;

class ContactSupportController extends BaseController
{
    /**
     * @var ContactSupportModel
     */
    protected $model;

    /**
     * ContactSupportController constructor.
     */
    public function __construct()
    {
        $this->model = new ContactSupportModel();
    }

    /**
     * @return array
     */
    public function index()
    {
        return ['status' => '0k'];
    }

    /**
     * @param Request $request
     * @param ContactSupportTransformer $contactSupportTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, ContactSupportTransformer $contactSupportTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $contactSupport = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($contactSupport, $contactSupportTransformer);
    }

    public function detail($id, ContactSupportTransformer $contactSupportTransformer)
    {
        $contactSupport = ContactSupport::find($id);
        if (empty($contactSupport)) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($contactSupport, $contactSupportTransformer);
    }

    public function create(Request $request, ContactSupportCreateValidator $contactSupportCreateValidator)
    {
        $input = $request->all();
        $input['status'] = "NEW";
        $contactSupportCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $contactSupport = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $contactSupport->id . "-" . $contactSupport->subject);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("contact_support.create-success", $contactSupport->subject)];
    }

    public function update($id, Request $request, ContactSupportUpdateValidator $contactSupportUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $contactSupportUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $contactSupport = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $contactSupport->id . "-" . $contactSupport->subject);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("contact_support.update-success", $contactSupport->subject)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $contactSupport = ContactSupport::find($id);
            if (empty($contactSupport)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            $contactSupport->delete();
            Log::delete($this->model->getTable(), "#ID:" . $contactSupport->subject);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("contact_support.delete-success", $contactSupport->subject)];
    }

    public function createContact(Request $request)
    {
        try{
            list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
            $input = $request->all();
            if(empty($input['name'])){
                return $this->responseError("Vui lòng nhập tên!!", 422);
            }
            if(empty($input['email'])){
                return $this->responseError("Vui lòng nhập email!!", 422);
            }
            if(empty($input['content'])){
                return $this->responseError("Vui lòng nhập nội dung!!", 422);
            }
            if(empty($input['subject'])){
                return $this->responseError("Vui lòng nhập tiêu đề!!", 422);
            }
            $company = Company::find($company_id);
            $user = User::find(TM::getCurrentUserId());
            $this->dispatch(new SendMailContact('ttt20100612@gmail.com', [
                    'name'              => str_clean_special_characters($input['name']),
                    'subject'           => str_clean_special_characters($input['subject']),
                    'email'             => str_clean_special_characters($input['email']),
                    'phone'             => !empty($user) ? $user->phone : null,
                    'content'           => str_clean_special_characters($input['content']),
                    'company_name'      => $company->name,
                    'company_email'     => $company->email,
                    'company_phone'     => $company->phone,
            ]));
            $mail_contact = new MailContact();
            $mail_contact->name  = str_clean_special_characters($input['name']);
            $mail_contact->email = str_clean_special_characters($input['email']);
            $mail_contact->subject = str_clean_special_characters($input['subject']);
            $mail_contact->content=str_clean_special_characters($input['content']);
            $mail_contact->company_id = $company_id;
            $mail_contact->store_id = $store_id;
            $mail_contact->save();
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => "Gửi phản hồi thành công"];
    }

    public function exportForm(Request $request)
    {
        try{
            $input = $request->all();
            if(empty($input['name'])){
                return $this->responseError("Vui lòng nhập tên!!", 422);
            }
            if(empty($input['email'])){
                return $this->responseError("Vui lòng nhập email!!", 422);
            }
            if(empty($input['phone'])){
                return $this->responseError("Vui lòng nhập số điện thoại!!", 422);
            }
            if(empty($input['country'])){
                return $this->responseError("Vui lòng nhập country!!", 422);
            }
            if(empty($input['inquiry'])){
                return $this->responseError("Vui lòng nhập số điện inquiry!!", 422);
            }
            if(empty($input['area_anwser_1'])){
                return $this->responseError("Vui lòng nhập area_anwser_1 !!", 422);
            }
            $please = !empty($input['please']) ? $input['please'] : null;
            $advise = !empty($input['advise']) ? $input['advise'] : null;
            $company = !empty($input['company']) ? $input['company'] : null;
            $this->dispatch(new SendMailExportForm('export@nutifood.com.vn', [
                'name'              => str_clean_special_characters($input['name']),
                'email'           => str_clean_special_characters($input['email']),
                'country'             => str_clean_special_characters($input['country']),
                'company'              => $company,
                'inquiry'           => str_clean_special_characters($input['inquiry']),
                'advise'             => str_clean_special_characters($advise),
                'please'             => str_clean_special_characters($please),
                'phone'         => str_clean_special_characters($input['phone']),
                'area_anwser_1'         => str_clean_special_characters($input['area_anwser_1'][0]),

            ]));
            $mail_contact = new ExportForm();
            $mail_contact->name  = str_clean_special_characters($input['name']);
            $mail_contact->email = str_clean_special_characters($input['email']);
            $mail_contact->country = str_clean_special_characters($input['country']);
            $mail_contact->company=str_clean_special_characters($company);
            $mail_contact->inquiry = str_clean_special_characters($input['inquiry']);
            $mail_contact->advise=str_clean_special_characters($advise);
            $mail_contact->phone=str_clean_special_characters($input['phone']);
            $mail_contact->please = str_clean_special_characters($please);
            $mail_contact->area_anwser_1 = str_clean_special_characters($input['area_anwser_1'][0]);
            $mail_contact->save();
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => "Gửi phản hồi thành công"];
    }
}
