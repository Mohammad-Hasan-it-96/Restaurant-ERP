<?php

namespace App\Http\Requests;

/**
 * Same rules as StoreOrderRequest but customer_name and customer_phone
 * are optional — they default to the original order's values in the service.
 */
class ModifyOrderRequest extends StoreOrderRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        // Name and phone are optional when modifying — backend fills from original order
        $rules['customer_name']  = ['nullable', 'string', 'min:2', 'max:100',
                                    'regex:/^[\p{L}\s\-\.\']+$/u'];
        $rules['customer_phone'] = ['nullable', 'string',
                                    'regex:/^[0-9\+\-\s\(\)]{7,20}$/'];

        return $rules;
    }
}

