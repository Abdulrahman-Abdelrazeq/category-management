<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Traits\Response;

class BaseFormRequest extends FormRequest
{
    use Response;
    protected function failedValidation(Validator $validator)
    {
        // Throw a custom HTTP response with validation errors and 422 Unprocessable Entity status
        throw new HttpResponseException($this->sendRes(false, 'Validation Error', null, $validator->errors(), 422));
    }
}
