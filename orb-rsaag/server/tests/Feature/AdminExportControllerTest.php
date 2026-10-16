<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class AdminExportControllerTest extends TestCase
{
    use WithoutMiddleware;

    protected static $exportUsersUri = '/admin/api/users/export';
    protected static $exportLogsUri = '/admin/api/export_logs';

    public function test_export_users_csv()
    {
        $response = $this->get(self::$exportUsersUri . '?mode=csv');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            'attachment; filename=users.csv',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_export_users_xlsx()
    {
        $response = $this->get(self::$exportUsersUri . '?mode=xlsx');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            'attachment; filename=users.xlsx',
            $response->headers->get('Content-Disposition')
        );
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
    }

    public function test_export_users_invalid_mode()
    {
        $response = $this->get(self::$exportUsersUri . '?mode=invalid');
        $this->assertJson(
            $response->getContent(),
            json_encode(['message' => 'Параметр mode может принимать значения только "csv" или "xlsx"'])
        );
    }

    public function test_export_logs_csv()
    {
        $response = $this->get(self::$exportLogsUri . '?mode=csv');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            'attachment; filename=logs.csv',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_export_logs_xlsx()
    {
        $response = $this->get(self::$exportLogsUri . '?mode=xlsx');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            'attachment; filename=logs.xlsx',
            $response->headers->get('Content-Disposition')
        );
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );

    }
    public function test_export_logs_invalid_mode()
    {
        $response = $this->get(self::$exportLogsUri . '?mode=invalid');
        $this->assertJson(
            $response->getContent(),
            json_encode(['message' => 'Параметр mode может принимать значения только "csv" или "xlsx"'])
        );
    }

}
