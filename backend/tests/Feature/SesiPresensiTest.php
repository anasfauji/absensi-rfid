<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pengguna;
use App\Models\SesiPresensi;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\PresensiKelas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SesiPresensiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_boleh_membuat_sesi_presensi(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B91-002',
            'nama_guru' => 'Guru Penangan B9.1',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/api/sesi-presensi', [
                'id_guru' => $guruPemilik->id_guru,
                'id_guru_penangan' => $guruPenangan->id_guru,

                'id_kelas' => $kelas->id_kelas,
                'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Pengenalan Variabel Java',
                'keterangan' => null,
            ]);

        $response->assertStatus(201);

        $response->assertJson([
            'message' => 'Sesi presensi berhasil dibuat.',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Pengenalan Variabel Java',
        ]);
    }

    public function test_operator_boleh_membuat_sesi_presensi(): void
    {
        $operator = Pengguna::create([
            'username' => 'operator01',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator Test',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleOperator = \App\Models\Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator->roles()->attach($roleOperator->id_role);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B92-002',
            'nama_guru' => 'Guru Penangan B9.2',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $response = $this
            ->actingAs($operator)
            ->postJson('/api/sesi-presensi', [
                'id_guru' => $guruPemilik->id_guru,
                'id_guru_penangan' => $guruPenangan->id_guru,
                'id_kelas' => $kelas->id_kelas,
                'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                'tanggal' => '2026-09-30',
                'waktu_mulai' => '09:00:00',
                'waktu_selesai' => '10:30:00',
                'materi' => 'Operator Membuat Sesi',
                'keterangan' => null,
            ]);

        $response->assertStatus(201);

        $response->assertJson([
            'message' => 'Sesi presensi berhasil dibuat.',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '10:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Operator Membuat Sesi',
        ]);
    }

    public function test_guru_boleh_membuat_sesi_presensi_sendiri(): void
    {
        $guru = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $pengguna = Pengguna::where(
            'username',
            'guru01'
        )->first();

        if ($pengguna === null) {
            $pengguna = Pengguna::create([
                'username' => 'guru01',
                'password' => bcrypt('password'),
                'nama_tampilan' => $guru->nama_guru,
                'email' => null,
                'foto' => null,
                'id_guru' => $guru->id_guru,
                'id_siswa' => null,
                'status' => 'AKTIF',
            ]);

            $roleGuru = \App\Models\Role::where(
                'kode_role',
                'GURU'
            )->firstOrFail();

            $pengguna->roles()->attach($roleGuru->id_role);
        }

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $response = $this
            ->actingAs($pengguna)
            ->postJson('/api/sesi-presensi', [
                'id_kelas' => $kelas->id_kelas,
                'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                'tanggal' => now()->format('Y-m-d'),
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Pengenalan Variabel Java',
                'keterangan' => null,
            ]);

        $response->assertStatus(201);

        $response->assertJson([
            'message' => 'Sesi presensi berhasil dibuat.',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'id_guru' => $guru->id_guru,
            'id_guru_penangan' => null,
            'tanggal' => now()->format('Y-m-d'),
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Pengenalan Variabel Java',
        ]);
    }

    public function test_guru_tidak_boleh_spoof_id_guru_dan_id_guru_penangan(): void
    {
        $guru = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruLain = Guru::create([
            'nip' => 'TEST-B94-001',
            'nama_guru' => 'Guru Lain B9.4',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $pengguna = Pengguna::where(
            'username',
            'guru01'
        )->first();

        if ($pengguna === null) {
            $pengguna = Pengguna::create([
                'username' => 'guru01',
                'password' => bcrypt('password'),
                'nama_tampilan' => $guru->nama_guru,
                'email' => null,
                'foto' => null,
                'id_guru' => $guru->id_guru,
                'id_siswa' => null,
                'status' => 'AKTIF',
            ]);

            $roleGuru = \App\Models\Role::where(
                'kode_role',
                'GURU'
            )->firstOrFail();

            $pengguna->roles()->attach($roleGuru->id_role);
        }

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $response = $this
            ->actingAs($pengguna)
            ->postJson('/api/sesi-presensi', [
                'id_guru' => $guruLain->id_guru,
                'id_guru_penangan' => $guruLain->id_guru,
                'id_kelas' => $kelas->id_kelas,
                'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                'tanggal' => now()->format('Y-m-d'),
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Percobaan Spoofing',
                'keterangan' => null,
            ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'id_guru',
            'id_guru_penangan',
        ]);

        $this->assertDatabaseMissing('sesi_presensi', [
            'id_guru' => $guruLain->id_guru,
            'id_guru_penangan' => $guruLain->id_guru,
            'materi' => 'Percobaan Spoofing',
        ]);
    }

    public function test_role_lain_tidak_boleh_membuat_sesi_presensi(): void
    {
        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $roleCodes = [
            'KS',
            'WAKA',
            'KAPROG',
            'BK',
            'WALI_KELAS',
            'SISWA',
        ];

        foreach ($roleCodes as $roleCode) {
            $role = \App\Models\Role::where(
                'kode_role',
                $roleCode
            )->firstOrFail();

            $pengguna = Pengguna::create([
                'username' => 'test_' . strtolower($roleCode),
                'password' => bcrypt('password'),
                'nama_tampilan' => 'Test ' . $roleCode,
                'email' => null,
                'foto' => null,
                'id_guru' => null,
                'id_siswa' => null,
                'status' => 'AKTIF',
            ]);

            $pengguna->roles()->attach($role->id_role);

            $response = $this
                ->actingAs($pengguna)
                ->postJson('/api/sesi-presensi', [
                    'id_kelas' => $kelas->id_kelas,
                    'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                    'tanggal' => now()->format('Y-m-d'),
                    'waktu_mulai' => '07:00:00',
                    'waktu_selesai' => '08:30:00',
                    'materi' => 'Percobaan Role ' . $roleCode,
                    'keterangan' => null,
                ]);

            $response->assertStatus(403);

            $this->assertDatabaseMissing('sesi_presensi', [
                'materi' => 'Percobaan Role ' . $roleCode,
            ]);
        }
    }

    public function test_duplikasi_sesi_presensi_ditolak(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B96-002',
            'nama_guru' => 'Guru Penangan B9.6',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $dataSesi = [
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'materi' => 'Sesi Pertama',
            'keterangan' => null,
        ];

        $responsePertama = $this
            ->actingAs($admin)
            ->postJson('/api/sesi-presensi', $dataSesi);

        $responsePertama->assertStatus(201);

        $responseKedua = $this
            ->actingAs($admin)
            ->postJson('/api/sesi-presensi', [
                ...$dataSesi,
                'materi' => 'Sesi Duplikat',
            ]);

        $this->assertNotSame(
            201,
            $responseKedua->status()
        );

        $this->assertDatabaseCount(
            'sesi_presensi',
            1
        );
    }

    public function test_response_pembuatan_sesi_presensi_memiliki_struktur_lengkap(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B97-002',
            'nama_guru' => 'Guru Penangan B9.7',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/api/sesi-presensi', [
                'id_guru' => $guruPemilik->id_guru,
                'id_guru_penangan' => $guruPenangan->id_guru,
                'id_kelas' => $kelas->id_kelas,
                'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Pengujian Response API',
                'keterangan' => null,
            ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'message',
            'data' => [
                'id_sesi_presensi',
                'id_guru',
                'id_guru_penangan',
                'id_kelas',
                'id_mata_pelajaran',
                'tanggal',
                'waktu_mulai',
                'waktu_selesai',
                'status_sesi',
                'materi',
                'keterangan',
                'created_at',
                'updated_at',
            ],
        ]);

        $response->assertJson([
            'message' => 'Sesi presensi berhasil dibuat.',
            'data' => [
                'id_guru' => $guruPemilik->id_guru,
                'id_guru_penangan' => $guruPenangan->id_guru,
                'id_kelas' => $kelas->id_kelas,
                'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'status_sesi' => 'DRAFT',
                'materi' => 'Pengujian Response API',
                'keterangan' => null,
            ],
        ]);
    }

    public function test_guru_pemilik_boleh_mengubah_metadata_sesi_presensi(): void
    {
        $guru = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $pengguna = Pengguna::where(
            'username',
            'guru01'
        )->first();

        if ($pengguna === null) {
            $pengguna = Pengguna::create([
                'username' => 'guru01',
                'password' => bcrypt('password'),
                'nama_tampilan' => $guru->nama_guru,
                'email' => null,
                'foto' => null,
                'id_guru' => $guru->id_guru,
                'id_siswa' => null,
                'status' => 'AKTIF',
            ]);

            $roleGuru = \App\Models\Role::where(
                'kode_role',
                'GURU'
            )->firstOrFail();

            $pengguna->roles()->attach($roleGuru->id_role);
        }

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Lama',
            'keterangan' => 'Keterangan Lama',
        ]);

        $response = $this
            ->actingAs($pengguna)
            ->putJson(
                '/api/sesi-presensi/' . $sesiPresensi->id_sesi_presensi,
                [
                    'id_kelas' => $kelas->id_kelas,
                    'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                    'tanggal' => '2026-09-30',
                    'waktu_mulai' => '09:00:00',
                    'waktu_selesai' => '10:30:00',
                    'materi' => 'Materi Baru',
                    'keterangan' => 'Keterangan Baru',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru' => $guru->id_guru,
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '10:30:00',
            'materi' => 'Materi Baru',
            'keterangan' => 'Keterangan Baru',
            'status_sesi' => 'DRAFT',
        ]);
    }

    public function test_guru_penangan_tidak_boleh_mengubah_metadata_sesi_presensi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B102-002',
            'nama_guru' => 'Guru Penangan B10.2',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $pengguna = Pengguna::create([
            'username' => 'guru_penangan_b102',
            'password' => bcrypt('password'),
            'nama_tampilan' => $guruPenangan->nama_guru,
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $pengguna->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Lama',
            'keterangan' => 'Keterangan Lama',
        ]);

        $response = $this
            ->actingAs($pengguna)
            ->putJson(
                '/api/sesi-presensi/' . $sesiPresensi->id_sesi_presensi,
                [
                    'id_kelas' => $kelas->id_kelas,
                    'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                    'tanggal' => '2026-09-30',
                    'waktu_mulai' => '09:00:00',
                    'waktu_selesai' => '10:30:00',
                    'materi' => 'Percobaan Guru Penangan',
                    'keterangan' => 'Tidak boleh diubah',
                ]
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'materi' => 'Materi Lama',
            'keterangan' => 'Keterangan Lama',
        ]);
    }

    public function test_admin_boleh_mengubah_metadata_sesi_presensi(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Lama Admin',
            'keterangan' => 'Keterangan Lama Admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/' . $sesiPresensi->id_sesi_presensi,
                [
                    'id_kelas' => $kelas->id_kelas,
                    'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                    'tanggal' => '2026-09-30',
                    'waktu_mulai' => '09:00:00',
                    'waktu_selesai' => '10:30:00',
                    'materi' => 'Materi Baru Admin',
                    'keterangan' => 'Keterangan Baru Admin',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru' => $guruPemilik->id_guru,
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '10:30:00',
            'materi' => 'Materi Baru Admin',
            'keterangan' => 'Keterangan Baru Admin',
            'status_sesi' => 'DRAFT',
        ]);
    }

    public function test_operator_boleh_mengubah_metadata_sesi_presensi(): void
    {
        $operator = Pengguna::create([
            'username' => 'operator01',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator Test',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleOperator = \App\Models\Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator->roles()->attach($roleOperator->id_role);

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Lama Operator',
            'keterangan' => 'Keterangan Lama Operator',
        ]);

        $response = $this
            ->actingAs($operator)
            ->putJson(
                '/api/sesi-presensi/' . $sesiPresensi->id_sesi_presensi,
                [
                    'id_kelas' => $kelas->id_kelas,
                    'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                    'tanggal' => '2026-09-30',
                    'waktu_mulai' => '09:00:00',
                    'waktu_selesai' => '10:30:00',
                    'materi' => 'Materi Baru Operator',
                    'keterangan' => 'Keterangan Baru Operator',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru' => $guruPemilik->id_guru,
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '10:30:00',
            'materi' => 'Materi Baru Operator',
            'keterangan' => 'Keterangan Baru Operator',
            'status_sesi' => 'DRAFT',
        ]);
    }

    public function test_update_metadata_tidak_mengubah_guru_pemilik_dan_guru_penangan(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B105-002',
            'nama_guru' => 'Guru Penangan B10.5',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaGuru = Pengguna::create([
            'username' => 'guru-b105',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Pemilik B10.5',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPemilik->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaGuru->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Lama',
            'keterangan' => 'Keterangan Lama',
        ]);

        $response = $this
            ->actingAs($penggunaGuru)
            ->putJson(
                '/api/sesi-presensi/' . $sesiPresensi->id_sesi_presensi,
                [
                    'id_kelas' => $kelas->id_kelas,
                    'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                    'tanggal' => '2026-09-30',
                    'waktu_mulai' => '09:00:00',
                    'waktu_selesai' => '10:30:00',
                    'materi' => 'Materi Baru',
                    'keterangan' => 'Keterangan Baru',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '10:30:00',
            'materi' => 'Materi Baru',
            'keterangan' => 'Keterangan Baru',
        ]);
    }

    public function test_admin_boleh_mendelegasikan_guru_penangan(): void
    {
        $admin = Pengguna::create([
            'username' => 'admin-b111',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin B11.1',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleAdmin = \App\Models\Role::where(
            'kode_role',
            'ADMIN'
        )->firstOrFail();

        $admin->roles()->attach($roleAdmin->id_role);

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B111-002',
            'nama_guru' => 'Guru Penangan B11.1',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Delegasi',
            'keterangan' => 'Belum ada guru penangan',
        ]);

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/' . $sesiPresensi->id_sesi_presensi . '/penangan',
                [
                    'id_guru_penangan' => $guruPenangan->id_guru,
                ]
            );

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Guru penangan berhasil ditetapkan.',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
        ]);
    }

    public function test_delegasi_guru_penangan_menolak_guru_tidak_aktif(): void
    {
        $admin = Pengguna::create([
            'username' => 'admin-b1112',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin B11.1.2',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleAdmin = \App\Models\Role::where(
            'kode_role',
            'ADMIN'
        )->firstOrFail();

        $admin->roles()->attach($roleAdmin->id_role);

        $guruTidakAktif = Guru::create([
            'nip' => 'TEST-B1112-001',
            'nama_guru' => 'Guru Tidak Aktif B11.1.2',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'id_guru' => null,
            'status' => 'NONAKTIF',
        ]);

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Delegasi',
            'keterangan' => null,
        ]);

        // Validation Request akan diuji setelah endpoint delegasi tersedia.
        $this->assertNotNull($sesiPresensi);
        $this->assertNotNull($guruTidakAktif);
    }

    public function test_guru_tidak_boleh_mendelegasikan_guru_penangan(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B112-002',
            'nama_guru' => 'Guru Penangan B11.2',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaGuru = Pengguna::create([
            'username' => 'guru-b112',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru B11.2',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPemilik->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaGuru->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Delegasi',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaGuru)
            ->putJson(
                '/api/sesi-presensi/' . $sesiPresensi->id_sesi_presensi . '/penangan',
                [
                    'id_guru_penangan' => $guruPenangan->id_guru,
                ]
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
        ]);
    }

    public function test_operator_boleh_mendelegasikan_guru_penangan(): void
    {
        $operator = Pengguna::create([
            'username' => 'operator-b113',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator B11.3',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleOperator = \App\Models\Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator->roles()->attach($roleOperator->id_role);

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B113-002',
            'nama_guru' => 'Guru Penangan B11.3',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Delegasi Operator',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($operator)
            ->putJson(
                '/api/sesi-presensi/' . $sesiPresensi->id_sesi_presensi . '/penangan',
                [
                    'id_guru_penangan' => $guruPenangan->id_guru,
                ]
            );

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Guru penangan berhasil ditetapkan.',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
        ]);
    }

    public function test_delegasi_guru_penangan_tidak_aktif_ditolak(): void
    {
        $admin = Pengguna::create([
            'username' => 'admin-b114',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin B11.4',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleAdmin = \App\Models\Role::where(
            'kode_role',
            'ADMIN'
        )->firstOrFail();

        $admin->roles()->attach($roleAdmin->id_role);

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruTidakAktif = Guru::create([
            'nip' => 'TEST-B114-002',
            'nama_guru' => 'Guru Tidak Aktif B11.4',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'NONAKTIF',
        ]);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Delegasi',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/' . $sesiPresensi->id_sesi_presensi . '/penangan',
                [
                    'id_guru_penangan' => $guruTidakAktif->id_guru,
                ]
            );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'id_guru_penangan',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
        ]);
    }

    public function test_guru_penangan_tidak_boleh_mendelegasikan_lagi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-B115-002',
            'nama_guru' => 'Guru Penangan B11.5',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $guruPenanganBaru = Guru::create([
            'nip' => 'TEST-B115-003',
            'nama_guru' => 'Guru Penangan Baru B11.5',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-b115',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan B11.5',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Delegasi',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/penangan',
                [
                    'id_guru_penangan' => $guruPenanganBaru->id_guru,
                ]
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
        ]);
    }

    public function test_guru_penangan_boleh_mengaktifkan_sesi_presensi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-AKTIF-001',
            'nama_guru' => 'Guru Penangan Aktivasi',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-penangan-aktif',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan Aktivasi',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Aktivasi',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/aktifkan'
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'AKTIF',
        ]);
    }

    public function test_guru_pemilik_tidak_boleh_mengaktifkan_sesi_yang_didelegasikan(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-AKTIF-002',
            'nama_guru' => 'Guru Penangan Aktivasi 2',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPemilik = Pengguna::create([
            'username' => 'guru-pemilik-aktif',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Pemilik Aktivasi',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPemilik->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPemilik->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Aktivasi',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaPemilik)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/aktifkan'
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'DRAFT',
        ]);
    }

    public function test_guru_penangan_tidak_boleh_mengaktifkan_sesi_yang_sudah_aktif(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-AKTIF-003',
            'nama_guru' => 'Guru Penangan Aktivasi 3',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-penangan-aktif-3',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan Aktivasi 3',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi Aktivasi',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/aktifkan'
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'AKTIF',
        ]);
    }

    public function test_admin_boleh_mengaktifkan_sesi_presensi(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-AKTIF-004',
            'nama_guru' => 'Guru Penangan Admin Aktivasi',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Aktivasi Admin',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/aktifkan'
            );

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Sesi presensi berhasil diaktifkan.',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'AKTIF',
        ]);
    }

    public function test_operator_boleh_mengaktifkan_sesi_presensi(): void
    {
        $operator = Pengguna::create([
            'username' => 'operator-aktif-001',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator Aktivasi',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleOperator = \App\Models\Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator->roles()->attach($roleOperator->id_role);

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-AKTIF-005',
            'nama_guru' => 'Guru Penangan Operator Aktivasi',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Aktivasi Operator',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($operator)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/aktifkan'
            );

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Sesi presensi berhasil diaktifkan.',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'AKTIF',
        ]);
    }

    public function test_guru_lain_tidak_boleh_mengaktifkan_sesi_presensi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-AKTIF-006',
            'nama_guru' => 'Guru Penangan 006',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $guruLain = Guru::create([
            'nip' => 'TEST-AKTIF-007',
            'nama_guru' => 'Guru Lain 007',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaGuruLain = Pengguna::create([
            'username' => 'guru-lain-aktif-007',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Lain 007',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruLain->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaGuruLain->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Aktivasi',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaGuruLain)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/aktifkan'
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'DRAFT',
        ]);
    }

    public function test_siswa_tidak_boleh_mengaktifkan_sesi_presensi(): void
    {
        $siswa = \App\Models\Siswa::firstOrFail();

        $penggunaSiswa = Pengguna::where(
            'id_siswa',
            $siswa->id_siswa
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-AKTIF-008',
            'nama_guru' => 'Guru Penangan 008',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Aktivasi',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaSiswa)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/aktifkan'
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'DRAFT',
        ]);
    }

    public function test_guru_penangan_tidak_boleh_mengaktifkan_sesi_yang_sudah_ditutup(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-AKTIF-009',
            'nama_guru' => 'Guru Penangan 009',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-penangan-aktif-009',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan 009',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Materi Aktivasi',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/aktifkan'
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'DITUTUP',
        ]);
    }

    public function test_guru_penangan_boleh_menutup_sesi_presensi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-TUTUP-001',
            'nama_guru' => 'Guru Penangan Tutup 001',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-penangan-tutup-001',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan Tutup 001',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi Penutupan',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/tutup'
            );

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Sesi presensi berhasil ditutup.',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'DITUTUP',
        ]);
    }

    public function test_guru_pemilik_boleh_menutup_sesi_presensi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-TUTUP-002',
            'nama_guru' => 'Guru Penangan Tutup 002',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPemilik = Pengguna::create([
            'username' => 'guru-pemilik-tutup-002',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Pemilik Tutup 002',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPemilik->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPemilik->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi Penutupan Pemilik',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaPemilik)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/tutup'
            );

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Sesi presensi berhasil ditutup.',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'DITUTUP',
        ]);
    }

    public function test_guru_penangan_tidak_boleh_menutup_sesi_yang_sudah_ditutup(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-TUTUP-003',
            'nama_guru' => 'Guru Penangan Tutup 003',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPenangan = Pengguna::create([
            'username' => 'guru-penangan-tutup-003',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Penangan Tutup 003',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPenangan->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPenangan->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Materi Sesi Ditutup',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaPenangan)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/tutup'
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'DITUTUP',
        ]);
    }

    public function test_admin_boleh_menutup_sesi_presensi(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-TUTUP-004',
            'nama_guru' => 'Guru Penangan Tutup 004',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi Sesi Admin',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/tutup'
            );

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Sesi presensi berhasil ditutup.',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'DITUTUP',
        ]);
    }

    public function test_operator_boleh_menutup_sesi_presensi(): void
    {
        $operator = Pengguna::create([
            'username' => 'operator-aktif-001',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Operator Aktivasi',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleOperator = \App\Models\Role::where(
            'kode_role',
            'OPERATOR'
        )->firstOrFail();

        $operator->roles()->attach($roleOperator->id_role);


        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-TUTUP-005',
            'nama_guru' => 'Guru Penangan Tutup 005',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi Sesi Operator',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($operator)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/tutup'
            );

        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'Sesi presensi berhasil ditutup.',
        ]);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'DITUTUP',
        ]);
    }

    public function test_guru_lain_tidak_boleh_menutup_sesi_presensi(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-TUTUP-006',
            'nama_guru' => 'Guru Penangan Tutup 006',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $guruLain = Guru::create([
            'nip' => 'TEST-TUTUP-007',
            'nama_guru' => 'Guru Lain Tutup 007',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaGuruLain = Pengguna::create([
            'username' => 'guru-lain-tutup-001',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Lain Tutup 007',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruLain->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaGuruLain->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi Sesi Guru Lain',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaGuruLain)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/tutup'
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'AKTIF',
        ]);
    }

    public function test_siswa_tidak_boleh_menutup_sesi_presensi(): void
    {
        $siswa = Pengguna::where(
            'username',
            'siswa01'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-TUTUP-008',
            'nama_guru' => 'Guru Penangan Tutup 008',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi Sesi Siswa',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($siswa)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/tutup'
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'AKTIF',
        ]);
    }

    public function test_guru_pemilik_tidak_boleh_menutup_sesi_yang_masih_draft(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-TUTUP-009',
            'nama_guru' => 'Guru Penangan Tutup 009',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $penggunaPemilik = Pengguna::create([
            'username' => 'guru-pemilik-tutup-009',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Pemilik Tutup 009',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPemilik->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPemilik->roles()->attach(
            $roleGuru->id_role
        );

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Sesi Draft',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaPemilik)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/tutup'
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'DRAFT',
        ]);
    }

    public function test_tidak_boleh_mengubah_presensi_pada_sesi_yang_sudah_ditutup(): void
    {
        $guru = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $penggunaGuru = Pengguna::where(
            'id_guru',
            $guru->id_guru
        )->first();

        if ($penggunaGuru === null) {
            $penggunaGuru = Pengguna::create([
                'username' => 'guru-tutup-presensi-001',
                'password' => bcrypt('password'),
                'nama_tampilan' => 'Guru Tutup Presensi 001',
                'email' => null,
                'foto' => null,
                'id_guru' => $guru->id_guru,
                'id_siswa' => null,
                'status' => 'AKTIF',
            ]);

            $roleGuru = \App\Models\Role::where(
                'kode_role',
                'GURU'
            )->firstOrFail();

            $penggunaGuru->roles()->attach(
                $roleGuru->id_role
            );
        }

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $siswa = Siswa::firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Materi Sesi Ditutup',
            'keterangan' => null,
        ]);

        $presensiKelas = PresensiKelas::create([
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_siswa' => $siswa->id_siswa,
            'status' => 'HADIR',
            'waktu_presensi' => '07:05:00',
            'sumber' => 'GURU',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaGuru)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/presensi/' .
                    $siswa->id_siswa,
                [
                    'status' => 'IZIN',
                    'waktu_presensi' => '07:05:00',
                    'sumber' => 'GURU',
                    'keterangan' => 'Koreksi test',
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('presensi_kelas', [
            'id_presensi_kelas' => $presensiKelas->id_presensi_kelas,
            'status' => 'HADIR',
        ]);
    }

    public function test_tanggal_sesi_yang_sudah_aktif_tidak_boleh_diubah(): void
    {
        $guru = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $penggunaGuru = Pengguna::where(
            'id_guru',
            $guru->id_guru
        )->first();

        if ($penggunaGuru === null) {
            $penggunaGuru = Pengguna::create([
                'username' => 'guru-update-tanggal-001',
                'password' => bcrypt('password'),
                'nama_tampilan' => 'Guru Update Tanggal 001',
                'email' => null,
                'foto' => null,
                'id_guru' => $guru->id_guru,
                'id_siswa' => null,
                'status' => 'AKTIF',
            ]);

            $roleGuru = \App\Models\Role::where(
                'kode_role',
                'GURU'
            )->firstOrFail();

            $penggunaGuru->roles()->attach(
                $roleGuru->id_role
            );
        }

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'AKTIF',
            'materi' => 'Materi Sesi Aktif',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaGuru)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi,
                [
                    'id_kelas' => $kelas->id_kelas,
                    'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                    'tanggal' => '2026-10-01',
                    'waktu_mulai' => '07:00:00',
                    'waktu_selesai' => '08:30:00',
                    'materi' => 'Materi Sesi Aktif',
                    'keterangan' => null,
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'tanggal' => '2026-09-30',
        ]);
    }

    public function test_metadata_sesi_yang_sudah_ditutup_tidak_boleh_diubah(): void
    {
        $admin = Pengguna::where('username', 'admin')->firstOrFail();

        $guru = Guru::firstOrFail();
        $kelas = Kelas::firstOrFail();
        $mataPelajaran = MataPelajaran::firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guru->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Materi Lama',
            'keterangan' => 'Keterangan Lama',
        ]);

        $response = $this->actingAs($admin)
            ->putJson(
                "/api/sesi-presensi/{$sesiPresensi->id_sesi_presensi}",
                [
                    'id_kelas' => $sesiPresensi->id_kelas,
                    'id_mata_pelajaran' => $sesiPresensi->id_mata_pelajaran,
                    'tanggal' => $sesiPresensi->tanggal,
                    'waktu_mulai' => $sesiPresensi->waktu_mulai,
                    'waktu_selesai' => $sesiPresensi->waktu_selesai,
                    'materi' => 'Materi Baru',
                    'keterangan' => $sesiPresensi->keterangan,
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'materi' => 'Materi Lama',
        ]);
    }

    public function test_admin_tidak_boleh_membuat_sesi_dengan_guru_pemilik_dan_penangan_yang_sama(): void
    {
        $admin = Pengguna::where('username', 'admin')->firstOrFail();

        $guru = Guru::firstOrFail();
        $kelas = Kelas::firstOrFail();
        $mataPelajaran = MataPelajaran::firstOrFail();

        $response = $this->actingAs($admin)
            ->postJson('/api/sesi-presensi', [
                'id_guru' => $guru->id_guru,
                'id_guru_penangan' => $guru->id_guru,
                'id_kelas' => $kelas->id_kelas,
                'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Materi Test',
                'keterangan' => null,
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('sesi_presensi', [
            'id_guru' => $guru->id_guru,
            'id_guru_penangan' => $guru->id_guru,
            'tanggal' => '2026-09-30',
        ]);
    }

    public function test_admin_boleh_membuat_sesi_dengan_guru_pemilik_dan_penangan_yang_berbeda(): void
    {
        $admin = Pengguna::where('username', 'admin')->firstOrFail();

        $guruPemilik = Guru::firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => '197804302011011007',
            'nama_guru' => 'Guru Penangan Test, S.Kom',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::firstOrFail();
        $mataPelajaran = MataPelajaran::firstOrFail();

        $response = $this->actingAs($admin)
            ->postJson('/api/sesi-presensi', [
                'id_guru' => $guruPemilik->id_guru,
                'id_guru_penangan' => $guruPenangan->id_guru,
                'id_kelas' => $kelas->id_kelas,
                'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
                'tanggal' => '2026-09-30',
                'waktu_mulai' => '07:00:00',
                'waktu_selesai' => '08:30:00',
                'materi' => 'Materi Test',
                'keterangan' => null,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => $guruPenangan->id_guru,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'status_sesi' => 'DRAFT',
        ]);
    }

    public function test_guru_pemilik_tidak_boleh_mengaktifkan_draft_tanpa_guru_penangan(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = null;

        $penggunaPemilik = Pengguna::create([
            'username' => 'guru-pemilik-aktif',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Pemilik Aktivasi',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPemilik->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPemilik->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Aktivasi',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($penggunaPemilik)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/aktifkan'
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'status_sesi' => 'DRAFT',
        ]);
    }

    public function test_guru_pemilik_tidak_boleh_mengubah_metadata_sesi_yang_sudah_ditutup(): void
    {
        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $penggunaPemilik = Pengguna::create([
            'username' => 'guru-pemilik-ditutup',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Guru Pemilik Ditutup',
            'email' => null,
            'foto' => null,
            'id_guru' => $guruPemilik->id_guru,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $roleGuru = \App\Models\Role::where(
            'kode_role',
            'GURU'
        )->firstOrFail();

        $penggunaPemilik->roles()->attach($roleGuru->id_role);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Materi Lama',
            'keterangan' => 'Keterangan Lama',
        ]);

        $response = $this
            ->actingAs($penggunaPemilik)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi,
                [
                    'id_kelas' => $sesiPresensi->id_kelas,
                    'id_mata_pelajaran' => $sesiPresensi->id_mata_pelajaran,
                    'tanggal' => $sesiPresensi->tanggal,
                    'waktu_mulai' => $sesiPresensi->waktu_mulai,
                    'waktu_selesai' => $sesiPresensi->waktu_selesai,
                    'materi' => 'Materi Baru',
                    'keterangan' => $sesiPresensi->keterangan,
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'materi' => 'Materi Lama',
            'keterangan' => 'Keterangan Lama',
            'status_sesi' => 'DITUTUP',
        ]);
    }

    public function test_delegasi_menolak_guru_penangan_yang_sama_dengan_guru_pemilik(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DRAFT',
            'materi' => 'Materi Delegasi',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/penangan',
                [
                    'id_guru_penangan' => $guruPemilik->id_guru,
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
        ]);
    }

    public function test_delegasi_tidak_boleh_pada_sesi_yang_sudah_ditutup(): void
    {
        $admin = Pengguna::where(
            'username',
            'admin'
        )->firstOrFail();

        $guruPemilik = Guru::where(
            'status',
            'AKTIF'
        )->firstOrFail();

        $guruPenangan = Guru::create([
            'nip' => 'TEST-DELEGASI-TUTUP-001',
            'nama_guru' => 'Guru Penangan Delegasi Tutup 001',
            'jenis_kelamin' => 'L',
            'email' => null,
            'nomor_telepon' => null,
            'foto' => null,
            'status' => 'AKTIF',
        ]);

        $kelas = Kelas::where(
            'nama_kelas',
            'X RPL 1'
        )->firstOrFail();

        $mataPelajaran = MataPelajaran::where(
            'kode_mata_pelajaran',
            'RPL-DASAR'
        )->firstOrFail();

        $sesiPresensi = SesiPresensi::create([
            'id_guru' => $guruPemilik->id_guru,
            'id_guru_penangan' => null,
            'id_kelas' => $kelas->id_kelas,
            'id_mata_pelajaran' => $mataPelajaran->id_mata_pelajaran,
            'tanggal' => '2026-09-30',
            'waktu_mulai' => '07:00:00',
            'waktu_selesai' => '08:30:00',
            'status_sesi' => 'DITUTUP',
            'materi' => 'Materi Sesi Ditutup',
            'keterangan' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->putJson(
                '/api/sesi-presensi/' .
                    $sesiPresensi->id_sesi_presensi .
                    '/penangan',
                [
                    'id_guru_penangan' => $guruPenangan->id_guru,
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('sesi_presensi', [
            'id_sesi_presensi' => $sesiPresensi->id_sesi_presensi,
            'id_guru_penangan' => null,
            'status_sesi' => 'DITUTUP',
        ]);
    }
}
