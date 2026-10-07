<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RfidEvent;
use Illuminate\Http\JsonResponse;

class RfidEventController extends Controller
{
    private const READ_COLUMNS = [
        'id_rfid_event',
        'id_perangkat_rfid',
        'id_kartu_rfid',
        'id_siswa',
        'uid_rfid',
        'waktu_event',
        'waktu_diterima_server',
        'status_proses',
        'hasil_event',
        'keterangan',
        'created_at',
        'updated_at',
    ];

    public function index(): JsonResponse
    {
        $events = RfidEvent::query()
            ->select(self::READ_COLUMNS)
            ->orderByDesc('waktu_diterima_server')
            ->orderByDesc('id_rfid_event')
            ->get();

        return response()->json([
            'message' => 'Daftar RFID event berhasil diambil.',
            'data' => [
                'rfid_event' => $events,
            ],
        ], 200);
    }

    public function show(int $id_rfid_event): JsonResponse
    {
        $event = RfidEvent::query()
            ->select(self::READ_COLUMNS)
            ->findOrFail($id_rfid_event);

        return response()->json([
            'message' => 'Detail RFID event berhasil diambil.',
            'data' => [
                'rfid_event' => $event,
            ],
        ], 200);
    }
}
