<?php
/**
 * User: dai.ho
 * Date: 5/03/2021
 * Time: 3:27 PM
 */

namespace App\Sync\Validators;


use App\Exceptions\DaiException;
use App\Supports\Message;
use App\TM;
use Dingo\Api\Exception\ValidationHttpException;
use Illuminate\Support\Facades\DB;
use Validator;

abstract class ValidatorBase
{
    abstract protected function rules();

    protected $_input;

    public function checkValidate($input)
    {
        $this->_input = $input;
        Validator::extend('unique_create', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->first();
            if (!empty($item)) {
                $validator->errors()->add($attribute, Message::get("V007", $attribute));
            }
            return true;
        });

        Validator::extend('unique_update', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->get()->toArray();
            if (!empty($item) && count($item) > 0) {
                if (count($item) > 1 || ($item[0]->id != $this->_input['id'])) {
                    $validator->errors()->add($attribute, Message::get("V007", $attribute));
                }
            }
            return true;
        });

        Validator::extend('unique_create_delete', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->whereNull('deleted_at')->first();
            if (!empty($item)) {
                $validator->errors()->add($attribute, Message::get("V007", $attribute));
            }
            return true;
        });

        Validator::extend('unique_update_delete', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->whereNull('deleted_at')->get()->toArray();
            if (!empty($item) && count($item) > 0) {
                if (count($item) > 1 || ($item[0]->id != $this->_input['id'])) {
                    $validator->errors()->add($attribute, Message::get("V007", $attribute));
                }
            }
            return true;
        });

        Validator::extend('unique_create_company_delete', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->whereNull('deleted_at')->where('company_id', TM::getCurrentCompanyId())->first();
            if (!empty($item)) {
                $validator->errors()->add($attribute, Message::get("V007", $attribute));
            }
            return true;
        });

        Validator::extend('unique_update_company_delete', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->whereNull('deleted_at')->where('company_id', TM::getCurrentCompanyId())->get()->toArray();
            if (!empty($item) && count($item) > 0) {
                if (count($item) > 1 || ($item[0]->id != $this->_input['id'])) {
                    $validator->errors()->add($attribute, Message::get("V007", $attribute));
                }
            }
            return true;
        });

        $validator = Validator::make($input, $this->rules(), $this->messages());
        $validator->setAttributeNames($this->attributes());

        return  $validator->errors()->getMessages();
    }

    public function validate($input)
    {
        $this->_input = $input;

        Validator::extend('unique_create', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->first();
            if (!empty($item)) {
                $validator->errors()->add($attribute, Message::get("V007", $attribute));
            }
            return true;
        });

        Validator::extend('unique_update', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->get()->toArray();
            if (!empty($item) && count($item) > 0) {
                if (count($item) > 1 || ($item[0]->id != $this->_input['id'])) {
                    $validator->errors()->add($attribute, Message::get("V007", $attribute));
                }
            }
            return true;
        });

        Validator::extend('unique_create_delete', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->whereNull('deleted_at')->first();
            if (!empty($item)) {
                $validator->errors()->add($attribute, Message::get("V007", $attribute));
            }
            return true;
        });

        Validator::extend('unique_update_delete', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->whereNull('deleted_at')->get()->toArray();
            if (!empty($item) && count($item) > 0) {
                if (count($item) > 1 || ($item[0]->id != $this->_input['id'])) {
                    $validator->errors()->add($attribute, Message::get("V007", $attribute));
                }
            }
            return true;
        });

        Validator::extend('unique_create_company_delete', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->whereNull('deleted_at')->where('company_id',
                TM::getCurrentCompanyId())->first();
            if (!empty($item)) {
                $validator->errors()->add($attribute, Message::get("V007", $attribute));
            }
            return true;
        });

        Validator::extend('unique_update_company_delete', function ($attribute, $value, $parameters, $validator) {
            $item = DB::table($parameters[0])->where($attribute, $value)->whereNull('deleted_at')->where('company_id',
                TM::getCurrentCompanyId())->get()->toArray();
            if (!empty($item) && count($item) > 0) {
                if (count($item) > 1 || ($item[0]->id != $this->_input['id'])) {
                    $validator->errors()->add($attribute, Message::get("V007", $attribute));
                }
            }
            return true;
        });

        $validator = Validator::make($input, $this->rules(), $this->messages());
        $validator->setAttributeNames($this->attributes());

        if ($validator->fails()) {
            $errors = $validator->errors()->getMessages();
            $msgs   = "";
            foreach ($errors as $key => $error) {
                $msgs .= "[$key]-" . (implode("|", $error)) . " ";
            }
//            throw new DaiException("Something went wrong", 400, null, $errors);
            throw new ValidationHttpException ($validator->errors()->getMessages());

        }

        return $validator;
    }

    protected function messages()
    {
        $attributes = array_keys(config('validation'));
        $output     = [];
        foreach ($attributes as $attribute) {
            $output["*.$attribute"] = Message::get($attribute, ':attribute');
        }
        return $output;
    }
}