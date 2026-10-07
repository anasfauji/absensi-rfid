<?php

namespace Tests\Feature;

use App\Models\KartuRfid;
use App\Models\Kalender;
use App\Models\Kegiatan;
use App\Models\KegiatanKelas;
use App\Models\Kelas;
use App\Models\Jurusan;
use App\Models\PerangkatRfid;
use App\Models\PenempatanSiswa;
use App\Models\PresensiGate;
use App\Models\RfidEvent;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\RfidTapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidTapServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_valid_tap_records_event_and_gate_using_server_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-07 07:15:30'));
        $context = $this->createContext();

        $result = $this->tap(
            $context['device'],
            $context['card'],
            '2026-10-06 23:58:00'
        );

        $this->assertSame('VALID', $result['data']['hasil']);
        $this->assertDatabaseHas('rfid_event', [
            'id_perangkat_rfid' => $context['device']->id_perangkat_rfid,
            'id_kartu_rfid' => $context['card']->id_kartu_rfid,
            'id_siswa' => $context['student']->id_siswa,
            'hasil_event' => 'VALID',
            'waktu_event' => '2026-10-06 23:58:00',
            'waktu_diterima_server' => '2026-10-07 07:15:30',
        ]);

        $gate = PresensiGate::query()->sole();
        $this->assertSame($context['student']->id_siswa, $gate->id_siswa);
        $this->assertSame('2026-10-07', $gate->tanggal);
        $this->assertSame('2026-10-07 07:15:30', $gate->waktu_masuk);
        $this->assertSame('RFID', $gate->sumber_masuk);
    }

    public function test_later_valid_tap_records_event_without_changing_gate_evidence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-07 07:10:00'));
        $context = $this->createContext();
        $firstResult = $this->tap($context['device'], $context['card']);

        Carbon::setTestNow(Carbon::parse('2026-10-07 07:20:21'));
        $secondResult = $this->tap($context['device'], $context['card']);

        $this->assertSame('VALID', $firstResult['data']['hasil']);
        $this->assertSame('VALID', $secondResult['data']['hasil']);
        $this->assertSame(2, RfidEvent::query()->count());
        $this->assertSame(1, PresensiGate::query()->count());

        $gate = PresensiGate::query()->sole();
        $this->assertSame('2026-10-07 07:10:00', $gate->waktu_masuk);
        $this->assertSame('RFID', $gate->sumber_masuk);
    }

    public function test_tap_in_debounce_window_is_recorded_as_duplicate_without_gate_changes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-07 07:10:00'));
        $context = $this->createContext();
        $this->tap($context['device'], $context['card']);
        $gateBefore = PresensiGate::query()->sole()->getAttributes();

        Carbon::setTestNow(Carbon::parse('2026-10-07 07:10:04'));
        $result = $this->tap($context['device'], $context['card']);

        $this->assertSame('DUPLIKAT', $result['data']['hasil']);
        $this->assertSame(
            ['VALID', 'DUPLIKAT'],
            RfidEvent::query()->orderBy('id_rfid_event')->pluck('hasil_event')->all()
        );
        $this->assertSame($gateBefore, PresensiGate::query()->sole()->getAttributes());
    }

    public function test_debounce_is_scoped_to_device_and_uid_combination(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-07 07:10:00'));
        $context = $this->createContext();
        $this->tap($context['device'], $context['card']);

        $otherDevice = $this->createDevice('GATE-02');
        Carbon::setTestNow(Carbon::parse('2026-10-07 07:10:01'));
        $otherDeviceResult = $this->tap($otherDevice, $context['card']);

        $otherCard = $this->createCard($context['student'], 'TEST-UID-002');
        Carbon::setTestNow(Carbon::parse('2026-10-07 07:10:02'));
        $otherUidResult = $this->tap($context['device'], $otherCard);

        $this->assertSame('VALID', $otherDeviceResult['data']['hasil']);
        $this->assertSame('VALID', $otherUidResult['data']['hasil']);
        $this->assertSame(3, RfidEvent::query()->count());
    }

    public function test_unknown_uid_is_logged_as_rejected_without_gate_evidence(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-07 07:10:00'));
        $context = $this->createContext();

        $result = app(RfidTapService::class)->process([
            'uid' => 'UNKNOWN-UID',
            'kode_perangkat' => $context['device']->kode_perangkat,
            'waktu_event' => '2026-10-07 07:10:00',
        ]);

        $this->assertSame('DITOLAK', $result['data']['hasil']);
        $this->assertDatabaseHas('rfid_event', [
            'uid_rfid' => 'UNKNOWN-UID',
            'id_perangkat_rfid' => $context['device']->id_perangkat_rfid,
            'id_kartu_rfid' => null,
            'id_siswa' => null,
            'hasil_event' => 'DITOLAK',
        ]);
        $this->assertDatabaseCount('presensi_gate', 0);
    }

    public function test_inactive_or_out_of_period_cards_are_rejected_without_gate(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-07 07:10:00'));
        $context = $this->createContext();
        $context['card']->status = 'NONAKTIF';
        $context['card']->save();

        $inactiveResult = $this->tap($context['device'], $context['card']);

        $notStartedCard = $this->createCard(
            $context['student'],
            'TEST-UID-003',
            '2026-10-08',
            null
        );
        $expiredCard = $this->createCard(
            $context['student'],
            'TEST-UID-004',
            '2026-10-01',
            '2026-10-06'
        );

        $notStartedResult = $this->tap($context['device'], $notStartedCard);
        $expiredResult = $this->tap($context['device'], $expiredCard);

        $this->assertSame('DITOLAK', $inactiveResult['data']['hasil']);
        $this->assertSame('DITOLAK', $notStartedResult['data']['hasil']);
        $this->assertSame('DITOLAK', $expiredResult['data']['hasil']);
        $this->assertSame(3, RfidEvent::query()->where('hasil_event', 'DITOLAK')->count());
        $this->assertDatabaseCount('presensi_gate', 0);
    }

    public function test_unregistered_or_inactive_device_is_rejected_without_event(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-07 07:10:00'));
        $context = $this->createContext();

        $unknownDeviceResult = app(RfidTapService::class)->process([
            'uid' => $context['card']->uid_rfid,
            'kode_perangkat' => 'UNKNOWN-GATE',
            'waktu_event' => '2026-10-07 07:10:00',
        ]);

        $context['device']->status = 'NONAKTIF';
        $context['device']->save();
        $inactiveDeviceResult = $this->tap($context['device'], $context['card']);

        $this->assertSame(422, $unknownDeviceResult['status_code']);
        $this->assertSame(422, $inactiveDeviceResult['status_code']);
        $this->assertSame(0, RfidEvent::query()->count());
        $this->assertDatabaseCount('presensi_gate', 0);
    }

    public function test_valid_tap_without_regular_attendance_eligibility_only_records_event(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-07 07:10:00'));
        $context = $this->createContext(createCalendar: false);

        $result = $this->tap($context['device'], $context['card']);

        $this->assertSame('VALID', $result['data']['hasil']);
        $this->assertSame(1, RfidEvent::query()->count());
        $this->assertDatabaseCount('presensi_gate', 0);
    }

    public function test_active_class_activity_suppresses_gate_evidence_but_keeps_rfid_event(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-07 07:10:00'));
        $context = $this->createContext();
        $activity = Kegiatan::query()->create([
            'id_tahun_ajaran' => $context['schoolYear']->id_tahun_ajaran,
            'nama_kegiatan' => 'Kegiatan kelas test',
            'jenis_kegiatan' => 'KEGIATAN_LAIN',
            'tanggal_mulai' => '2026-10-07',
            'tanggal_selesai' => '2026-10-07',
            'status' => 'AKTIF',
        ]);
        KegiatanKelas::query()->create([
            'id_kegiatan' => $activity->id_kegiatan,
            'id_kelas' => $context['class']->id_kelas,
        ]);

        $result = $this->tap($context['device'], $context['card']);

        $this->assertSame('VALID', $result['data']['hasil']);
        $this->assertSame(1, RfidEvent::query()->count());
        $this->assertDatabaseCount('presensi_gate', 0);
    }

    private function createContext(bool $createCalendar = true): array
    {
        $school = Sekolah::query()->create([
            'kode_sekolah' => 'RFID-TEST',
            'npsn' => 'RFID-TEST-001',
            'status' => 'AKTIF',
        ]);

        $schoolYear = TahunAjaran::query()->create([
            'id_sekolah' => $school->id_sekolah,
            'nama_tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'semester_aktif' => 'GANJIL',
            'status' => 'AKTIF',
        ]);

        $department = Jurusan::query()->create([
            'id_sekolah' => $school->id_sekolah,
            'kode_jurusan' => 'RFID',
            'nama_jurusan' => 'Jurusan RFID Test',
            'status' => 'AKTIF',
        ]);

        $class = Kelas::query()->create([
            'id_tahun_ajaran' => $schoolYear->id_tahun_ajaran,
            'id_jurusan' => $department->id_jurusan,
            'tingkat' => 10,
            'nama_kelas' => 'X RFID Test',
            'status' => 'AKTIF',
        ]);

        $student = Siswa::query()->create([
            'nis' => 'RFID-TEST-001',
            'nisn' => 'RFID-NISN-001',
            'nama_siswa' => 'Siswa RFID Test',
            'jenis_kelamin' => 'L',
            'status' => 'AKTIF',
        ]);

        PenempatanSiswa::query()->create([
            'id_siswa' => $student->id_siswa,
            'id_kelas' => $class->id_kelas,
            'tanggal_mulai' => '2026-07-01',
            'status' => 'AKTIF',
        ]);

        if ($createCalendar) {
            Kalender::query()->create([
                'id_tahun_ajaran' => $schoolYear->id_tahun_ajaran,
                'tanggal' => '2026-10-07',
                'status_hari' => 'AKTIF',
            ]);
        }

        $device = $this->createDevice('GATE-01');
        $card = $this->createCard($student, 'TEST-UID-001');

        return compact('school', 'schoolYear', 'department', 'class', 'student', 'device', 'card');
    }

    private function createDevice(string $code): PerangkatRfid
    {
        return PerangkatRfid::query()->create([
            'kode_perangkat' => $code,
            'nama_perangkat' => 'Gate Test ' . $code,
            'lokasi' => 'Gerbang test',
            'jenis_perangkat' => 'ESP32_RFID',
            'status' => 'AKTIF',
        ]);
    }

    private function createCard(
        Siswa $student,
        string $uid,
        string $tanggalMulai = '2026-10-01',
        ?string $tanggalSelesai = null
    ): KartuRfid {
        return KartuRfid::query()->create([
            'id_siswa' => $student->id_siswa,
            'uid_rfid' => $uid,
            'status' => 'AKTIF',
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
        ]);
    }

    private function tap(
        PerangkatRfid $device,
        KartuRfid $card,
        ?string $waktuEvent = null
    ): array {
        return app(RfidTapService::class)->process([
            'uid' => $card->uid_rfid,
            'kode_perangkat' => $device->kode_perangkat,
            'waktu_event' => $waktuEvent ?? now()->toDateTimeString(),
        ]);
    }
}
