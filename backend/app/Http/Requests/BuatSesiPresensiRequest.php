<?php

namespace App\Http\Requests;

use App\Services\SesiPresensiAuthorization;
use App\Models\Guru;
use Illuminate\Validation\Rule;

use Illuminate\Foundation\Http\FormRequest;

class BuatSesiPresensiRequest extends FormRequest
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

            'id_guru' => $this->userIsAdminOrOperator()
                ? [
                    'required',
                    'integer',
                    Rule::exists('guru', 'id_guru')
                        ->where('status', 'AKTIF'),
                ]
                : [
                    'prohibited',
                ],

            'id_guru_penangan' => $this->userIsAdminOrOperator()
                ? [
                    'required',
                    'integer',
                    Rule::exists('guru', 'id_guru')
                        ->where('status', 'AKTIF'),
                    'different:id_guru',
                ]
                : [
                    'prohibited',
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

    private function userIsAdminOrOperator(): bool
    {
        return $this->user()
            ?->roles()
            ->whereIn('kode_role', [
                'ADMIN',
                'OPERATOR',
            ])
            ->exists() ?? false;
    }
}
