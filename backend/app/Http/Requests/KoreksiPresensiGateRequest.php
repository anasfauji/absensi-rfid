<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KoreksiPresensiGateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'waktu_masuk' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'sumber_masuk' => [
                'sometimes',
                'required',
                Rule::in(['RFID', 'MANUAL', 'SYSTEM']),
            ],
            'keterangan' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (['waktu_masuk', 'sumber_masuk', 'keterangan'] as $field) {
                if (array_key_exists($field, $this->all())) {
                    return;
                }
            }

            $validator->errors()->add(
                'koreksi',
                'Isi setidaknya satu field evidence yang akan dikoreksi.'
            );
        });
    }
}
