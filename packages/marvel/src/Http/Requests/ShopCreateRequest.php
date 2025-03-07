<?php

namespace Marvel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class ShopCreateRequest extends FormRequest
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
            'categories'             => ['nullable','array'],
            'is_active'              => ['boolean'],
            'description'            => ['nullable', 'string', 'max:10000'],
            'admin_commission_rate'  => ['nullable', 'numeric'],
            'total_earnings'         => ['nullable', 'numeric'],
            'withdrawn_amount'       => ['nullable', 'numeric'],
            'current_balance'        => ['nullable', 'numeric'],
            'image'                  => ['nullable', 'array'],
            'cover_image'            => ['nullable', 'array'],
            'settings'               => ['array'],
            'address'                => ['array'],

            'loginDetails'                  => ['nullable', 'array'],
            'loginDetails.email' => [
                'required_with:loginDetails',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'loginDetails.password'         => ['required_with:loginDetails', 'string', 'min:8', 'confirmed'],
            'loginDetails.confirmpassword'  => ['required_with:loginDetails', 'string', 'same:loginDetails.password'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
