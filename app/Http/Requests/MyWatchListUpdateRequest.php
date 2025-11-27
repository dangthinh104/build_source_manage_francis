<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MyWatchListUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'ticket_name' => ['required', 'string', 'max:5'],
            'id' => ['required'],
        ];
    }
}
