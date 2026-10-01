<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pengguna;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Http\Requests\BuatSesiPresensiRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BuatSesiPresensiRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_data_buat_sesi_presensi_valid(): void
    {
        $data = [
            'id_kelas' => 1,
            'id_mata_pelajaran' => 1,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'materi' => 'Pengenalan Variabel Java',
            'keterangan' => null,
        ];

        $validator = validator(
            $data,
            (new \App\Http\Requests\BuatSesiPresensiRequest())->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_admin_wajib_mengirim_id_guru_penangan(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $this->actingAs($admin);

        $request = BuatSesiPresensiRequest::create(
            '/api/sesi-presensi',
            'POST',
            [
                'id_kelas' => Kelas::where(
                    'nama_kelas',
                    'X RPL 1'
                )->firstOrFail()->id_kelas,

                'id_mata_pelajaran' => MataPelajaran::where(
                    'kode_mata_pelajaran',
                    'RPL-DASAR'
                )->firstOrFail()->id_mata_pelajaran,

                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Test B.8.1',
                'keterangan' => 'Fixture test validation.',
            ]
        );

        $request->setUserResolver(
            fn() => $admin
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );


        $this->assertTrue(
            $validator->fails()
        );

        $this->assertTrue(
            $validator->errors()->has('id_guru_penangan')
        );
    }

    public function test_id_guru_penangan_harus_terdaftar(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $request = BuatSesiPresensiRequest::create(
            '/api/sesi-presensi',
            'POST',
            [
                'id_kelas' => Kelas::where(
                    'nama_kelas',
                    'X RPL 1'
                )->firstOrFail()->id_kelas,

                'id_mata_pelajaran' => MataPelajaran::where(
                    'kode_mata_pelajaran',
                    'RPL-DASAR'
                )->firstOrFail()->id_mata_pelajaran,

                'id_guru_penangan' => [
                    'required',
                    'integer',
                    'exists:guru,id_guru',
                ],

                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Test B.8.2',
                'keterangan' => 'Fixture test validation.',
            ]
        );

        $request->setUserResolver(
            fn() => $admin
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertTrue(
            $validator->errors()->has('id_guru_penangan')
        );
    }

    public function test_guru_penangan_harus_aktif(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruTidakAktif = \App\Models\Guru::create([
            'nip' => 'TEST-PENANGAN-010',
            'nama_guru' => 'Guru Penangan Nonaktif Test 10',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'NONAKTIF',
        ]);

        $request = BuatSesiPresensiRequest::create(
            '/api/sesi-presensi',
            'POST',
            [
                'id_kelas' => Kelas::where(
                    'nama_kelas',
                    'X RPL 1'
                )->firstOrFail()->id_kelas,

                'id_mata_pelajaran' => MataPelajaran::where(
                    'kode_mata_pelajaran',
                    'RPL-DASAR'
                )->firstOrFail()->id_mata_pelajaran,

                'id_guru_penangan' => $guruTidakAktif->id_guru,

                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Test B.8.3',
                'keterangan' => 'Fixture test validation.',
            ]
        );

        $request->setUserResolver(
            fn() => $admin
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertTrue(
            $validator->errors()->has('id_guru_penangan')
        );
    }

    public function test_guru_penangan_aktif_lolos_validasi(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruAktif = \App\Models\Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $request = BuatSesiPresensiRequest::create(
            '/api/sesi-presensi',
            'POST',
            [
                'id_guru' => $guruAktif->id_guru,

                'id_kelas' => Kelas::where(
                    'nama_kelas',
                    'X RPL 1'
                )->firstOrFail()->id_kelas,

                'id_mata_pelajaran' => MataPelajaran::where(
                    'kode_mata_pelajaran',
                    'RPL-DASAR'
                )->firstOrFail()->id_mata_pelajaran,

                'id_guru_penangan' => $guruAktif->id_guru,

                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Test B.8.4',
                'keterangan' => 'Fixture test validation.',

            ]
        );

        $request->setUserResolver(
            fn() => $admin
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertFalse(
            $validator->fails()
        );
    }

    public function test_admin_wajib_mengirim_id_guru(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guru = \App\Models\Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $request = BuatSesiPresensiRequest::create(
            '/api/sesi-presensi',
            'POST',
            [
                'id_kelas' => Kelas::where(
                    'nama_kelas',
                    'X RPL 1'
                )->firstOrFail()->id_kelas,

                'id_mata_pelajaran' => MataPelajaran::where(
                    'kode_mata_pelajaran',
                    'RPL-DASAR'
                )->firstOrFail()->id_mata_pelajaran,

                // sengaja tidak mengirim id_guru

                'id_guru_penangan' => $guru->id_guru,

                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Test B.8.5',
                'keterangan' => 'Fixture test validation.',
            ]
        );

        $request->setUserResolver(
            fn() => $admin
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertTrue(
            $validator->errors()->has('id_guru')
        );
    }

    public function test_id_guru_harus_terdaftar(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruPenangan = \App\Models\Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $request = BuatSesiPresensiRequest::create(
            '/api/sesi-presensi',
            'POST',
            [
                'id_guru' => 999999,

                'id_kelas' => Kelas::where(
                    'nama_kelas',
                    'X RPL 1'
                )->firstOrFail()->id_kelas,

                'id_mata_pelajaran' => MataPelajaran::where(
                    'kode_mata_pelajaran',
                    'RPL-DASAR'
                )->firstOrFail()->id_mata_pelajaran,

                'id_guru_penangan' => $guruPenangan->id_guru,

                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Test B.8.6',
                'keterangan' => 'Fixture test validation.',
            ]
        );

        $request->setUserResolver(
            fn() => $admin
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertTrue(
            $validator->errors()->has('id_guru')
        );
    }

    public function test_guru_tidak_boleh_menentukan_id_guru_melalui_request(): void
    {
        $guru = \App\Models\Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $akunGuru = Pengguna::create([
            'username' => 'guru_b87',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru B87',
            'id_guru' => $guru->id_guru,
            'status' => 'AKTIF',
        ]);

        $guruLain = \App\Models\Guru::create([
            'nip' => 'TEST-B87-002',
            'nama_guru' => 'Guru Lain B87',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $request = BuatSesiPresensiRequest::create(
            '/api/sesi-presensi',
            'POST',
            [
                // Guru mencoba menentukan pemilik sesi secara manual
                'id_guru' => $guruLain->id_guru,

                'id_kelas' => Kelas::where(
                    'nama_kelas',
                    'X RPL 1'
                )->firstOrFail()->id_kelas,

                'id_mata_pelajaran' => MataPelajaran::where(
                    'kode_mata_pelajaran',
                    'RPL-DASAR'
                )->firstOrFail()->id_mata_pelajaran,

                'id_guru_penangan' => null,

                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Test B.8.7',
                'keterangan' => 'Fixture test validation.',
            ]
        );

        $request->setUserResolver(
            fn() => $akunGuru
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertTrue(
            $validator->errors()->has('id_guru')
        );
    }

    public function test_guru_tidak_boleh_menentukan_id_guru_penangan(): void
    {
        $guru = \App\Models\Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $akunGuru = Pengguna::create([
            'username' => 'guru_b88',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru B88',
            'id_guru' => $guru->id_guru,
            'status' => 'AKTIF',
        ]);

        $guruLain = \App\Models\Guru::create([
            'nip' => 'TEST-B88-002',
            'nama_guru' => 'Guru Penangan B88',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $request = BuatSesiPresensiRequest::create(
            '/api/sesi-presensi',
            'POST',
            [
                'id_guru' => null,

                'id_kelas' => Kelas::where(
                    'nama_kelas',
                    'X RPL 1'
                )->firstOrFail()->id_kelas,

                'id_mata_pelajaran' => MataPelajaran::where(
                    'kode_mata_pelajaran',
                    'RPL-DASAR'
                )->firstOrFail()->id_mata_pelajaran,

                // Guru mencoba menunjuk Guru Penangan
                'id_guru_penangan' => $guruLain->id_guru,

                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Test B.8.8',
                'keterangan' => 'Fixture test validation.',
            ]
        );

        $request->setUserResolver(
            fn() => $akunGuru
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertTrue(
            $validator->errors()->has('id_guru_penangan')
        );
    }

    public function test_guru_boleh_membuat_request_tanpa_id_guru_dan_penangan(): void
    {
        $guru = \App\Models\Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $akunGuru = Pengguna::create([
            'username' => 'guru_b89',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru B89',
            'id_guru' => $guru->id_guru,
            'status' => 'AKTIF',
        ]);

        $request = BuatSesiPresensiRequest::create(
            '/api/sesi-presensi',
            'POST',
            [
                'id_kelas' => Kelas::where(
                    'nama_kelas',
                    'X RPL 1'
                )->firstOrFail()->id_kelas,

                'id_mata_pelajaran' => MataPelajaran::where(
                    'kode_mata_pelajaran',
                    'RPL-DASAR'
                )->firstOrFail()->id_mata_pelajaran,

                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Test B.8.9',
                'keterangan' => 'Fixture test validation.',
            ]
        );

        $request->setUserResolver(
            fn() => $akunGuru
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertFalse(
            $validator->fails()
        );
    }

    public function test_operator_wajib_mengirim_id_guru(): void
    {
        $operator = Pengguna::create([
            'username' => 'operator_b810',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator B8.10',
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleOperator = \App\Models\Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator->roles()->attach($roleOperator->id_role);

        $guruPenangan = \App\Models\Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $request = BuatSesiPresensiRequest::create(
            '/api/sesi-presensi',
            'POST',
            [
                // sengaja tidak mengirim id_guru

                'id_kelas' => Kelas::where(
                    'nama_kelas',
                    'X RPL 1'
                )->firstOrFail()->id_kelas,

                'id_mata_pelajaran' => MataPelajaran::where(
                    'kode_mata_pelajaran',
                    'RPL-DASAR'
                )->firstOrFail()->id_mata_pelajaran,

                'id_guru_penangan' => $guruPenangan->id_guru,

                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Test B.8.10',
                'keterangan' => 'Fixture test validation.',
            ]
        );

        $request->setUserResolver(
            fn() => $operator
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertTrue(
            $validator->errors()->has('id_guru')
        );
    }

    public function test_operator_wajib_mengirim_id_guru_penangan(): void
    {
        $operator = Pengguna::create([
            'username' => 'operator_b811',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator B8.11',
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleOperator = \App\Models\Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator->roles()->attach($roleOperator->id_role);

        $guru = \App\Models\Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $request = BuatSesiPresensiRequest::create(
            '/api/sesi-presensi',
            'POST',
            [
                'id_guru' => $guru->id_guru,

                'id_kelas' => Kelas::where(
                    'nama_kelas',
                    'X RPL 1'
                )->firstOrFail()->id_kelas,

                'id_mata_pelajaran' => MataPelajaran::where(
                    'kode_mata_pelajaran',
                    'RPL-DASAR'
                )->firstOrFail()->id_mata_pelajaran,

                // sengaja tidak mengirim id_guru_penangan

                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Test B.8.11',
                'keterangan' => 'Fixture test validation.',
            ]
        );

        $request->setUserResolver(
            fn() => $operator
        );

        $validator = Validator::make(
            $request->all(),
            $request->rules()
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertTrue(
            $validator->errors()->has('id_guru_penangan')
        );
    }
}
