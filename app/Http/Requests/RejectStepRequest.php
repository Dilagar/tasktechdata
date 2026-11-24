<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
