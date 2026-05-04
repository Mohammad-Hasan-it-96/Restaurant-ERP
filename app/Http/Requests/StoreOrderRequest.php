<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint
    }

    public function rules(): array
    {
        $rules = [
            // Customer
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|max:30',

            // Order type
            'order_type'     => ['required', 'in:' . implode(',', [
                Order::TYPE_TABLE,
                Order::TYPE_DELIVERY,
                Order::TYPE_TAKEAWAY,
            ])],

            // Customer note
            'customer_note'  => 'nullable|string|max:1000',

            // Items
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1|max:100',
        ];

        $type = $this->input('order_type');

        if ($type === Order::TYPE_TABLE) {
            $rules['table_number'] = 'required|string|max:50';
        }

        if ($type === Order::TYPE_DELIVERY) {
            $rules['address']      = 'required|string|max:500';
            $rules['delivery_type'] = 'required|in:immediate,scheduled';

            if ($this->input('delivery_type') === 'scheduled') {
                $rules['scheduled_at'] = 'required|date|after:now';
            }
        }

        // Optional: estimated delivery fee hint from frontend
        $rules['estimated_delivery_fee'] = 'nullable|numeric|min:0';

        return $rules;
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'يجب إضافة منتج واحد على الأقل.',
            'items.*.product_id.exists'   => 'أحد المنتجات المختارة غير موجود.',
            'items.*.quantity.min'        => 'الكمية يجب أن تكون 1 على الأقل.',
            'table_number.required'       => 'رقم الطاولة مطلوب لطلبات المطعم.',
            'address.required'            => 'العنوان مطلوب لطلبات التوصيل.',
            'delivery_type.required'      => 'نوع التوصيل مطلوب (فوري أو مجدول).',
            'scheduled_at.required'       => 'وقت الجدولة مطلوب للتوصيل المجدول.',
            'scheduled_at.after'          => 'وقت الجدولة يجب أن يكون في المستقبل.',
        ];
    }
}

