<?php

namespace Tests\Unit;

use App\Http\Resources\AttendanceEvaluationResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class AttendanceEvaluationResourceTest extends TestCase
{
    public function test_resource_menghasilkan_status_hadir(): void
    {
        $data = [
            'status' => 'HADIR',
            'wajib_hadir' => true,
        ];

        $resource = new AttendanceEvaluationResource($data);

        $hasil = $resource->toArray(
            Request::create('/')
        );

        $this->assertSame('HADIR', $hasil['status']);
        $this->assertTrue($hasil['wajib_hadir']);
    }

    public function test_resource_mempertahankan_status_null(): void
    {
        $data = [
            'status' => null,
            'wajib_hadir' => true,
        ];

        $resource = new AttendanceEvaluationResource($data);

        $hasil = $resource->toArray(
            Request::create('/')
        );

        $this->assertNull($hasil['status']);
        $this->assertTrue($hasil['wajib_hadir']);
    }

    public function test_resource_menghasilkan_tidak_berlaku(): void
{
    $data = [
        'status' => 'TIDAK_BERLAKU',
        'wajib_hadir' => false,
    ];

    $resource = new AttendanceEvaluationResource($data);

    $hasil = $resource->toArray(
        Request::create('/')
    );

    $this->assertSame('TIDAK_BERLAKU', $hasil['status']);
    $this->assertFalse($hasil['wajib_hadir']);
}

public function test_resource_mempertahankan_data_tidak_lengkap(): void
{
    $data = [
        'status' => 'DATA_TIDAK_LENGKAP',
        'wajib_hadir' => null,
    ];

    $resource = new AttendanceEvaluationResource($data);

    $hasil = $resource->toArray(
        Request::create('/')
    );

    $this->assertSame(
        'DATA_TIDAK_LENGKAP',
        $hasil['status']
    );

    $this->assertNull(
        $hasil['wajib_hadir']
    );
}
public function test_resource_menghasilkan_alpa(): void
{
    $data = [
        'status' => 'ALPA',
        'wajib_hadir' => true,
    ];

    $resource = new AttendanceEvaluationResource($data);

    $hasil = $resource->toArray(
        Request::create('/')
    );

    $this->assertSame('ALPA', $hasil['status']);
    $this->assertTrue($hasil['wajib_hadir']);
}

}
