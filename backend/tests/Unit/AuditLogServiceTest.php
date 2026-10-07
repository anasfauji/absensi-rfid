<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Pengguna;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_dapat_mencatat_audit_log(): void
    {
        $pengguna = Pengguna::create([
            'username' => 'admin-audit-test',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin Audit Test',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $service = app(AuditLogService::class);

        $auditLog = $service->catat(
            pengguna: $pengguna,
            aksi: 'CREATE',
            namaTabel: 'presensi_kelas',
            idData: 1,
        );

        $this->assertInstanceOf(
            AuditLog::class,
            $auditLog
        );

        $this->assertDatabaseHas('audit_log', [
            'id_audit_log' => $auditLog->id_audit_log,
            'id_pengguna' => $pengguna->id_pengguna,
            'aksi' => 'CREATE',
            'nama_tabel' => 'presensi_kelas',
            'id_data' => '1',
        ]);
    }

    public function test_service_menyimpan_data_sebelum_dan_sesudah(): void
    {
        $pengguna = Pengguna::create([
            'username' => 'admin-audit-before-after',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin Audit Before After',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $service = app(AuditLogService::class);

        $auditLog = $service->catat(
            pengguna: $pengguna,
            aksi: 'KOREKSI',
            namaTabel: 'presensi_kelas',
            idData: 10,
            dataSebelum: [
                'status' => 'HADIR',
                'waktu_presensi' => '07:05:00',
            ],
            dataSesudah: [
                'status' => 'IZIN',
                'waktu_presensi' => '07:05:00',
            ],
        );

        $this->assertEquals(
            'HADIR',
            $auditLog->data_sebelum['status']
        );

        $this->assertEquals(
            'IZIN',
            $auditLog->data_sesudah['status']
        );

        $this->assertDatabaseHas('audit_log', [
            'id_audit_log' => $auditLog->id_audit_log,
        ]);
    }

    public function test_service_menyimpan_alasan_audit(): void
    {
        $pengguna = Pengguna::create([
            'username' => 'admin-audit-alasan',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin Audit Alasan',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $service = app(AuditLogService::class);

        $alasan = 'Siswa sebenarnya izin, tetapi sebelumnya tercatat hadir.';

        $auditLog = $service->catat(
            pengguna: $pengguna,
            aksi: 'KOREKSI',
            namaTabel: 'presensi_kelas',
            idData: 20,
            alasan: $alasan,
        );

        $this->assertEquals(
            $alasan,
            $auditLog->alasan
        );

        $this->assertDatabaseHas('audit_log', [
            'id_audit_log' => $auditLog->id_audit_log,
            'alasan' => $alasan,
        ]);
    }

    public function test_service_menyimpan_alamat_ip_dan_user_agent(): void
    {
        $pengguna = Pengguna::create([
            'username' => 'admin-audit-request',
            'password' => bcrypt('password'),
            'nama_tampilan' => 'Admin Audit Request',
            'email' => null,
            'foto' => null,
            'id_guru' => null,
            'id_siswa' => null,
            'status' => 'AKTIF',
        ]);

        $request = Request::create(
            '/api/test',
            'POST',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'ELIES-Test-Agent/1.0',
            ]
        );

        $service = new AuditLogService($request);

        $auditLog = $service->catat(
            pengguna: $pengguna,
            aksi: 'KOREKSI',
            namaTabel: 'presensi_kelas',
            idData: 30,
        );

        $this->assertEquals(
            '127.0.0.1',
            $auditLog->alamat_ip
        );

        $this->assertEquals(
            'ELIES-Test-Agent/1.0',
            $auditLog->user_agent
        );
    }
}