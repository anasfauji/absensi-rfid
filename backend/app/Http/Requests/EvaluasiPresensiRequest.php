<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvaluasiPresensiRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id_siswa' => $this->route('id_siswa'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id_siswa' => [
                'required',
                'integer',
                'exists:siswa,id_siswa',
            ],

            'tanggal' => [
                'required',
                'date_format:Y-m-d',
            ],
        ];
    }
}