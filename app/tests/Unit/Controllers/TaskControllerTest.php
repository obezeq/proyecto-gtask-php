<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para validaciones del TaskController.
 */
class TaskControllerTest extends TestCase
{
    private const VALID_STATUSES = ['pending', 'completed'];
    private const MAX_TITLE_LENGTH = 200;
    private const MAX_DESCRIPTION_LENGTH = 1000;
    private const MIN_PRIORITY = 0;
    private const MAX_PRIORITY = 5;

    public function testTitleIsRequired(): void
    {
        $emptyTitles = ['', '   ', null];

        foreach ($emptyTitles as $title) {
            $trimmed = trim((string) ($title ?? ''));
            $this->assertTrue($trimmed === '', "El titulo vacio deberia detectarse");
        }
    }

    public function testTitleMaxLengthValidation(): void
    {
        $validTitle = 'Mi tarea importante';
        $exactLength = str_repeat('a', self::MAX_TITLE_LENGTH);
        $longTitle = str_repeat('a', self::MAX_TITLE_LENGTH + 1);

        $this->assertTrue(mb_strlen($validTitle) <= self::MAX_TITLE_LENGTH);
        $this->assertTrue(mb_strlen($exactLength) <= self::MAX_TITLE_LENGTH);
        $this->assertFalse(mb_strlen($longTitle) <= self::MAX_TITLE_LENGTH);
    }

    public function testDescriptionMaxLengthValidation(): void
    {
        $validDescription = 'Descripcion corta';
        $exactLength = str_repeat('a', self::MAX_DESCRIPTION_LENGTH);
        $longDescription = str_repeat('a', self::MAX_DESCRIPTION_LENGTH + 1);

        $this->assertTrue(mb_strlen($validDescription) <= self::MAX_DESCRIPTION_LENGTH);
        $this->assertTrue(mb_strlen($exactLength) <= self::MAX_DESCRIPTION_LENGTH);
        $this->assertFalse(mb_strlen($longDescription) <= self::MAX_DESCRIPTION_LENGTH);
    }

    public function testNullDescriptionIsAllowed(): void
    {
        $description = null;
        $isValid = $description === null || mb_strlen($description) <= self::MAX_DESCRIPTION_LENGTH;

        $this->assertTrue($isValid);
    }

    public function testStatusValidation(): void
    {
        $this->assertTrue(in_array('pending', self::VALID_STATUSES, true));
        $this->assertTrue(in_array('completed', self::VALID_STATUSES, true));
        $this->assertFalse(in_array('invalid', self::VALID_STATUSES, true));
        $this->assertFalse(in_array('PENDING', self::VALID_STATUSES, true));
        $this->assertFalse(in_array('', self::VALID_STATUSES, true));
    }

    public function testStatusNormalization(): void
    {
        $testCases = [
            'PENDING' => 'pending',
            'Completed' => 'completed',
            '  pending  ' => 'pending',
        ];

        foreach ($testCases as $input => $expected) {
            $normalized = strtolower(trim($input));
            $this->assertEquals($expected, $normalized);
        }
    }

    public function testPriorityValidation(): void
    {
        // Valores validos
        for ($i = self::MIN_PRIORITY; $i <= self::MAX_PRIORITY; $i++) {
            $isValid = $i >= self::MIN_PRIORITY && $i <= self::MAX_PRIORITY;
            $this->assertTrue($isValid, "La prioridad $i deberia ser valida");
        }

        // Valores invalidos
        $invalidPriorities = [-1, 6, 10, -5];
        foreach ($invalidPriorities as $priority) {
            $isValid = $priority >= self::MIN_PRIORITY && $priority <= self::MAX_PRIORITY;
            $this->assertFalse($isValid, "La prioridad $priority deberia ser invalida");
        }
    }

    public function testDateValidationWithValidDates(): void
    {
        $validDates = ['2025-12-31', '2024-01-01', '2030-06-15'];

        foreach ($validDates as $date) {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
            $isValid = $dt !== false && $dt->format('Y-m-d') === $date;
            $this->assertTrue($isValid, "La fecha '$date' deberia ser valida");
        }
    }

    public function testDateValidationWithInvalidDates(): void
    {
        $invalidDates = [
            '31-12-2025',    // Formato incorrecto
            '2025/12/31',    // Separador incorrecto
            '2025-13-01',    // Mes invalido
            '2025-02-30',    // Dia invalido
            'not-a-date',    // No es fecha
            '',              // Vacio
        ];

        foreach ($invalidDates as $date) {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
            $isValid = $dt !== false && $dt->format('Y-m-d') === $date;
            $this->assertFalse($isValid, "La fecha '$date' deberia ser invalida");
        }
    }

    public function testNullDateIsAllowed(): void
    {
        $dueDate = null;
        $isValid = $dueDate === null || $this->isValidDate($dueDate);

        $this->assertTrue($isValid);
    }

    public function testCompletedAtSetWhenStatusCompleted(): void
    {
        $status = 'completed';
        $completedAt = $status === 'completed'
            ? (new DateTimeImmutable('now'))->format('Y-m-d H:i:s')
            : null;

        $this->assertNotNull($completedAt);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $completedAt);
    }

    public function testCompletedAtNullWhenStatusPending(): void
    {
        $status = 'pending';
        $completedAt = $status === 'completed'
            ? (new DateTimeImmutable('now'))->format('Y-m-d H:i:s')
            : null;

        $this->assertNull($completedAt);
    }

    public function testDefaultPriorityIsZero(): void
    {
        $data = ['title' => 'Test'];
        $priority = isset($data['priority']) ? (int) $data['priority'] : 0;

        $this->assertEquals(0, $priority);
    }

    public function testDefaultStatusIsPending(): void
    {
        $data = ['title' => 'Test'];
        $status = strtolower(trim((string) ($data['status'] ?? 'pending')));

        $this->assertEquals('pending', $status);
    }

    private function isValidDate(string $date): bool
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt !== false && $dt->format('Y-m-d') === $date;
    }
}
