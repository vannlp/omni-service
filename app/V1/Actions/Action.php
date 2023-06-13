<?php

namespace App\V1\Actions;

use Illuminate\Http\Request;

abstract class Action
{
    protected $errors;
    protected $request;
    protected $isSuccess = false;

    abstract public function doIt();

    public function validate(){}

    public function __construct()
    {
        $this->errors = collect([]);
        $this->setRequest();
    }

    public function handle()
    {
        $this->validate();

        if ( ! $this->isPassValidate() ) {
            return;
        }

        $this->doIt();
    }

    public function errors()
    {
        return $this->errors;
    }

    public function setRequest(array $data = [])
    {
        if ( ! $data ) {
            $this->request = app('request');
            return $this;
        }

        $this->request = new Request($data);
        return $this;
    }

    protected function isSuccess()
    {
        return $this->isSuccess;
    }

    protected function isPassValidate()
    {
        return $this->errors->count() == 0;
    }

    protected function addError($error)
    {
        $this->errors->push($error);
    }
}