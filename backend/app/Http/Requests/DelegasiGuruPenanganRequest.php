<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DelegasiGuruPenanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id_guru_penangan' => [
                'required',
                'integer',
                Rule::exists('guru', 'id_guru')
                    ->where('status', 'AKTIF'),
            ],
        ];
    }
}