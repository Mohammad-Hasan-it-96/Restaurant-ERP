<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Support\Feature;
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
            // ── Customer ─────────────────────────────────────────────────────
            // Name: 2–100 chars, letters + spaces + unicode (Arabic/English)
            'customer_name' => ['required', 'string', 'min:2', 'max:100',
                'regex:/^[\p{L}\s\-\.\']+$/u'],

            // Phone: digits, +, -, spaces, parentheses — 7 to 20 chars
            'customer_phone' => ['required', 'string',
                'regex:/^[0-9\+\-\s\(\)]{7,20}$/'],

            // ── Order type ───────────────────────────────────────────────────
            // Only channels whose core.* feature is enabled are accepted. If
            // none are enabled the list is empty and any order_type fails — but
            // defaults are on, so this only bites when a client deliberately
            // disables a channel.
            'order_type' => ['required', 'in:'.implode(',', $this->enabledOrderTypes())],

            // ── Items — max 20 distinct lines, each qty ≤ 50 ─────────────────
            'items' => 'required|array|min:1|max:20',
            'items.*.product_id' => 'required|integer|exists:products,id,is_active,1,is_available,1',
            'items.*.quantity' => 'required|integer|min:1|max:50',

            // Weight/option input is only permitted when its feature is on —
            // otherwise prohibited so a forged request can't inject it.
            'items.*.weight_id' => Feature::enabled('products.weight_products')
                                        ? 'nullable|integer|exists:weights,id'
                                        : 'prohibited',
            'items.*.option_name' => Feature::enabled('products.options')
                                        ? 'nullable|string|max:100'
                                        : 'prohibited',

            // ── Customer note ─────────────────────────────────────────────────
            'customer_note' => 'nullable|string|max:500',
        ];

        $type = $this->input('order_type');

        if ($type === Order::TYPE_TABLE) {
            $rules['table_number'] = 'required|string|min:1|max:50';
        }

        if ($type === Order::TYPE_DELIVERY) {
            $rules['address'] = 'required|string|min:10|max:500';
            $rules['delivery_type'] = 'required|in:immediate,scheduled';

            if ($this->input('delivery_type') === 'scheduled') {
                // Must be in the future but at most 30 days ahead
                $rules['scheduled_at'] = 'required|date|after:now|before:+30 days';
            }
        }

        // Optional delivery fee hint from frontend
        $rules['estimated_delivery_fee'] = 'nullable|numeric|min:0|max:99999';

        return $rules;
    }

    /**
     * Order types whose channel feature is currently enabled.
     *
     * @return string[]
     */
    private function enabledOrderTypes(): array
    {
        return array_values(array_filter([
            Feature::enabled('core.table_ordering') ? Order::TYPE_TABLE : null,
            Feature::enabled('core.delivery') ? Order::TYPE_DELIVERY : null,
            Feature::enabled('core.takeaway') ? Order::TYPE_TAKEAWAY : null,
        ]));
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'الاسم الكامل مطلوب.',
            'customer_name.min' => 'الاسم يجب أن يكون حرفين على الأقل.',
            'customer_name.regex' => 'الاسم يجب أن يحتوي على أحرف فقط.',
            'customer_phone.required' => 'رقم الهاتف مطلوب.',
            'customer_phone.regex' => 'رقم الهاتف غير صحيح.',
            'items.required' => 'يجب إضافة منتج واحد على الأقل.',
            'items.max' => 'لا يمكن إضافة أكثر من 20 صنفاً في طلب واحد.',
            'items.*.product_id.exists' => 'أحد المنتجات المختارة غير موجود أو غير متاح حالياً.',
            'items.*.quantity.min' => 'الكمية يجب أن تكون 1 على الأقل.',
            'items.*.quantity.max' => 'الكمية لا يمكن أن تتجاوز 50 لكل صنف.',
            'table_number.required' => 'رقم الطاولة مطلوب لطلبات المطعم.',
            'address.required' => 'العنوان مطلوب لطلبات التوصيل.',
            'address.min' => 'الرجاء إدخال عنوان أوضح (10 أحرف على الأقل).',
            'delivery_type.required' => 'نوع التوصيل مطلوب (فوري أو مجدول).',
            'scheduled_at.required' => 'وقت الجدولة مطلوب للتوصيل المجدول.',
            'scheduled_at.after' => 'وقت الجدولة يجب أن يكون في المستقبل.',
            'scheduled_at.before' => 'لا يمكن جدولة طلب بعد 30 يوماً من الآن.',
        ];
    }

    /**
     * Prepare the data for validation — sanitize strings.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_name' => trim($this->input('customer_name', '')),
            'customer_phone' => trim(preg_replace('/\s+/', ' ', $this->input('customer_phone', ''))),
            'customer_note' => $this->input('customer_note') !== null
                                    ? strip_tags(trim($this->input('customer_note')))
                                    : null,
        ]);
    }
}
