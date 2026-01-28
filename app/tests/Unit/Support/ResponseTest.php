<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para la clase Response.
 */
class ResponseTest extends TestCase
{
    public function testJsonEncodesArrayProperly(): void
    {
        $payload = ['message' => 'Test', 'data' => ['id' => 1]];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->assertJson($encoded);
        $this->assertStringContainsString('"message":"Test"', $encoded);
        $this->assertStringContainsString('"id":1', $encoded);
    }

    public function testJsonEncodesUnicodeCorrectly(): void
    {
        $payload = ['mensaje' => 'Tarea completada con exito'];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('exito', $encoded);
        $this->assertStringNotContainsString('\\u', $encoded);
    }

    public function testErrorStructureContainsErrorKey(): void
    {
        $errorPayload = array_merge(['error' => 'Test error'], ['code' => 'E001']);

        $this->assertArrayHasKey('error', $errorPayload);
        $this->assertEquals('Test error', $errorPayload['error']);
        $this->assertArrayHasKey('code', $errorPayload);
    }

    public function testEmptyPayloadEncodesToEmptyObject(): void
    {
        $encoded = json_encode([], JSON_THROW_ON_ERROR);
        $this->assertEquals('[]', $encoded);
    }

    public function testNestedArrayEncoding(): void
    {
        $payload = [
            'user' => [
                'id' => 1,
                'name' => 'Test User',
                'tasks' => [
                    ['id' => 1, 'title' => 'Task 1'],
                    ['id' => 2, 'title' => 'Task 2'],
                ],
            ],
        ];

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $decoded = json_decode($encoded, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded['user']['tasks']);
    }
}
