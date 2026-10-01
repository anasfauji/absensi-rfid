<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UbahSesiPresensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id_kelas' => [
                'required',
                'integer',
                'exists:kelas,id_kelas',
            ],

            'id_mata_pelajaran' => [
                'required',
                'integer',
                'exists:mata_pelajaran,id_mata_pelajaran',
            ],

            'tanggal' => [
                'required',
                'date_format:Y-m-d',
            ],

            'waktu_mulai' => [
                'required',
                'date_format:H:i:s',
            ],

            'waktu_selesai' => [
                'nullable',
                'date_format:H:i:s',
                'after:waktu_mulai',
            ],

            'materi' => [
                'nullable',
                'string',
                'max:255',
            ],

            'keterangan' => [
                'nullable',
                'string',
            ],
        ];
    }
}