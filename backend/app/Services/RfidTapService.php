<?php

namespace App\Services;

use App\Models\KartuRfid;
use App\Models\Kalender;
use App\Models\Kegiatan;
use App\Models\PerangkatRfid;
use App\Models\PresensiGate;
use App\Models\RfidEvent;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RfidTapService
{
    public function __construct(
        private StudentPlacementResolver $studentPlacementResolver
    ) {}

    public function process(array $input): array
    {
        $uid = strtoupper(trim($input['uid']));
        $kodePerangkat = trim($input['kode_perangkat']);
        $waktuDiterimaServer = now();
        $waktuEvent = isset($input['waktu_event'])
            ? Carbon::parse($input['waktu_event'])
            : $waktuDiterimaServer->copy();

        $perangkat = PerangkatRfid::query()
            ->where('kode_perangkat', $kodePerangkat)
            ->first();

        if ($perangkat === null) {
            return $this->rejection(
                'Perangkat RFID tidak terdaftar.',
                $uid,
                $kodePerangkat
            );
        }

        if ($perangkat->status !== 'AKTIF') {
            return $this->rejection(
                'Perangkat RFID tidak aktif.',
                $uid,
                $kodePerangkat
            );
        }

        $kartu = KartuRfid::query()
            ->where('uid_rfid', $uid)
            ->first();

        if ($this->isWithinDebounceWindow(
            $perangkat->id_perangkat_rfid,
            $uid,
            $waktuDiterimaServer
        )) {
            $event = $this->recordEvent(
                $perangkat,
                $kartu,
                $uid,
                $waktuEvent,
                $waktuDiterimaServer,
                'DUPLIKAT',
                'Tap RFID duplikat dalam jendela debounce 5 detik.'
            );

            return $this->eventResult(
                'Tap RFID terdeteksi sebagai duplikat.',
                'DUPLIKAT',
                $uid,
                $kartu?->id_siswa,
                $event->id_rfid_event,
                $waktuEvent
            );
        }

        if ($kartu === null) {
            $event = $this->recordEvent(
                $perangkat,
                null,
                $uid,
                $waktuEvent,
                $waktuDiterimaServer,
                'DITOLAK',
                'UID RFID tidak terdaftar.'
            );

            return $this->eventResult(
                'RFID tidak terdaftar.',
                'DITOLAK',
                $uid,
                null,
                $event->id_rfid_event,
                $waktuEvent
            );
        }

        if (! $this->cardIsValid($kartu, $waktuEvent)) {
            $event = $this->recordEvent(
                $perangkat,
                $kartu,
                $uid,
                $waktuEvent,
                $waktuDiterimaServer,
                'DITOLAK',
                'Kartu RFID tidak aktif atau berada di luar masa berlaku.'
            );

            return $this->eventResult(
                'Kartu RFID tidak aktif atau tidak berlaku.',
                'DITOLAK',
                $uid,
                $kartu->id_siswa,
                $event->id_rfid_event,
                $waktuEvent
            );
        }

        return DB::transaction(function () use (
            $perangkat,
            $kartu,
            $uid,
            $waktuEvent,
            $waktuDiterimaServer
        ) {
            $event = $this->recordEvent(
                $perangkat,
                $kartu,
                $uid,
                $waktuEvent,
                $waktuDiterimaServer,
                'VALID',
                'Pembacaan RFID valid.'
            );

            if ($this->isRegularAttendanceEligible(
                $kartu->id_siswa,
                $waktuDiterimaServer
            ) === true) {
                PresensiGate::query()->firstOrCreate(
                    [
                        'id_siswa' => $kartu->id_siswa,
                        'tanggal' => $waktuDiterimaServer->toDateString(),
                    ],
                    [
                        'waktu_masuk' => $waktuDiterimaServer,
                        'sumber_masuk' => 'RFID',
                    ]
                );
            }

            return $this->eventResult(
                'RFID berhasil diterima.',
                'VALID',
                $uid,
                $kartu->id_siswa,
                $event->id_rfid_event,
                $waktuEvent
            );
        });
    }

    private function isWithinDebounceWindow(
        int $idPerangkat,
        string $uid,
        CarbonInterface $waktuDiterimaServer
    ): bool {
        return RfidEvent::query()
            ->where('id_perangkat_rfid', $idPerangkat)
            ->where('uid_rfid', $uid)
            ->where('waktu_diterima_server', '>=', $waktuDiterimaServer->copy()->subSeconds(5))
            ->exists();
    }

    private function cardIsValid(KartuRfid $kartu, CarbonInterface $waktuEvent): bool
    {
        $tanggalEvent = $waktuEvent->toDateString();

        return $kartu->status === 'AKTIF'
            && $kartu->tanggal_mulai <= $tanggalEvent
            && (
                $kartu->tanggal_selesai === null
                || $kartu->tanggal_selesai >= $tanggalEvent
        );
    }

    private function isRegularAttendanceEligible(
        int $idSiswa,
        CarbonInterface $waktuDiterimaServer
    ): ?bool {
        $tanggal = $waktuDiterimaServer->toDateString();
        $kalender = Kalender::query()
            ->whereDate('tanggal', $tanggal)
            ->first();

        if ($kalender === null || $kalender->status_hari !== 'AKTIF') {
            return false;
        }

        $penempatan = $this->studentPlacementResolver
            ->getEffectivePlacement($idSiswa, $waktuDiterimaServer);

        if ($penempatan === null) {
            return null;
        }

        $kegiatanAktif = Kegiatan::query()
            ->where('id_tahun_ajaran', $kalender->id_tahun_ajaran)
            ->where('status', 'AKTIF')
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->get();

        foreach ($kegiatanAktif as $kegiatan) {
            if ($kegiatan->kegiatanSiswa()->where('id_siswa', $idSiswa)->exists()) {
                return false;
            }

            if ($kegiatan->kegiatanKelas()
                ->where('id_kelas', $penempatan->id_kelas)
                ->exists()) {
                return false;
            }

            $idJurusan = $penempatan->kelas?->id_jurusan;
            if ($idJurusan !== null && $kegiatan->kegiatanJurusan()
                ->where('id_jurusan', $idJurusan)
                ->exists()) {
                return false;
            }
        }

        return true;
    }

    private function recordEvent(
        PerangkatRfid $perangkat,
        ?KartuRfid $kartu,
        string $uid,
        CarbonInterface $waktuEvent,
        CarbonInterface $waktuDiterimaServer,
        string $hasilEvent,
        string $keterangan
    ): RfidEvent {
        return RfidEvent::query()->create([
            'id_perangkat_rfid' => $perangkat->id_perangkat_rfid,
            'id_kartu_rfid' => $kartu?->id_kartu_rfid,
            'id_siswa' => $kartu?->id_siswa,
            'uid_rfid' => $uid,
            'waktu_event' => $waktuEvent,
            'waktu_diterima_server' => $waktuDiterimaServer,
            'status_proses' => 'SELESAI',
            'hasil_event' => $hasilEvent,
            'keterangan' => $keterangan,
        ]);
    }

    private function rejection(string $message, string $uid, string $kodePerangkat): array
    {
        return [
            'message' => $message,
            'status_code' => 422,
            'data' => [
                'hasil' => 'DITOLAK',
                'uid_rfid' => $uid,
                'kode_perangkat' => $kodePerangkat,
            ],
        ];
    }

    private function eventResult(
        string $message,
        string $hasil,
        string $uid,
        ?int $idSiswa,
        int $idRfidEvent,
        CarbonInterface $waktuEvent
    ): array {
        return [
            'message' => $message,
            'status_code' => 200,
            'data' => [
                'hasil' => $hasil,
                'uid_rfid' => $uid,
                'id_siswa' => $idSiswa,
                'id_rfid_event' => $idRfidEvent,
                'waktu_event' => $waktuEvent->toDateTimeString(),
            ],
        ];
    }
}
