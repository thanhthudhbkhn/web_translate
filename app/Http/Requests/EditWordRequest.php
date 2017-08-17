<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditWordRequest extends FormRequest
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
            'keyword'=> 'unique:wt_keyword|required|regex:/^([a-zA-zぁ-ゔゞァ-・ヽヾ゛゜ー一-龯]+)$/',
            'translate'=> 'required',
        ];
    }
}
