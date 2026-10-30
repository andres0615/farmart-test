<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispensationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * El header `Idempotency-Key` se valida junto al resto del payload.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:255'],
            'prescription_id' => ['required', 'integer', 'exists:prescriptions,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.prescription_item_id' => ['required', 'integer', 'distinct', 'exists:prescription_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'authorized_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'idempotency_key.required' => 'Falta el header Idempotency-Key.',
            'idempotency_key.max' => 'El header Idempotency-Key es demasiado largo.',
            'prescription_id.required' => 'Indica la prescripción a dispensar.',
            'prescription_id.exists' => 'La prescripción indicada no existe.',
            'warehouse_id.required' => 'Indica la bodega de la dispensación.',
            'warehouse_id.exists' => 'La bodega indicada no existe.',
            'items.required' => 'Indica al menos un ítem a dispensar.',
            'items.array' => 'Los ítems deben enviarse como una lista.',
            'items.min' => 'Indica al menos un ítem a dispensar.',
            'items.*.prescription_item_id.required' => 'Cada ítem requiere su prescription_item_id.',
            'items.*.prescription_item_id.exists' => 'Uno de los ítems de prescripción no existe.',
            'items.*.prescription_item_id.distinct' => 'No repitas el mismo ítem de prescripción.',
            'items.*.quantity.required' => 'Cada ítem requiere una cantidad.',
            'items.*.quantity.integer' => 'Las cantidades deben ser enteras.',
            'items.*.quantity.min' => 'Las cantidades deben ser mayores a cero.',
            'authorized_by.exists' => 'El usuario autorizante indicado no existe.',
        ];
    }
}
