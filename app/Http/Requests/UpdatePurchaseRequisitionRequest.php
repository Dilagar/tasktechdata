<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cost_centre_id' => ['required', 'exists:cost_centres,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.uom_code' => ['required', 'exists:units_of_measure,code'],
            'lines.*.estimated_unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
