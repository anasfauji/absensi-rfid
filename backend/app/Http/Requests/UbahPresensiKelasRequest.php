<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbahPresensiKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    'HADIR',
                    'TERLAMBAT',
                    'SAKIT',
                    'IZIN',
                    'DISPENSASI',
                    'ALPA',
                ]),
            ],

            'waktu_presensi' => [
                'nullable',
                'date_format:H:i:s',
            ],

            'sumber' => [
                'required',
                Rule::in([
                    'SISTEM',
                    'GURU',
                    'WALI_KELAS',
                ]),
            ],

            'keterangan' => [
                'nullable',
                'string',
            ],
        ];
    }
}
