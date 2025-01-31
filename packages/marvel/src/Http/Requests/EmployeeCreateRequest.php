<?php

namespace Marvel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class EmployeeCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'                   => ['required', 'string', 'max:255'],
            'Employee_email'         => ['required', 'string', 'max:255', 'unique:users,email'],
            'categories'             => ['array'],
            'company_name'           => ['nullable', 'string', 'max:255'],
            'shop_id'                => ['required', 'exists:shops,id'],
            'gender'                 => ['nullable', 'string', 'max:255'],
            'contact_no'             => ['nullable', 'string', 'max:255'],
            'is_active'              => ['boolean'],
            'description'            => ['nullable', 'string', 'max:10000'],
            'withdrawn_amount'       => ['nullable', 'numeric'],
            'current_balance'        => ['nullable', 'numeric'],
            'image'                  => ['nullable', 'array'],
            'address'                => ['array'],
            'contact_info'           => ['array'],
            'password'               => ['required', 'string', 'min:8', 'confirmed'], // Use 'confirmed' here
            'confirmpassword'        => ['required', 'string', 'same:password'], // This line can be removed
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
