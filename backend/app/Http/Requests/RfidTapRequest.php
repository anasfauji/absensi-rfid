<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RfidTapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uid' => [
                'required',
                'string',
                'max:100',
            ],

            'kode_perangkat' => [
                'required',
                'string',
                'max:50',
            ],

            'waktu_event' => [
                'nullable',
                'date',
            ],
        ];
    }
}
