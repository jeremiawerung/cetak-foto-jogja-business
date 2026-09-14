<?php

namespace App\Services\KartuPelajar;

final class FieldResult
{
    public function __construct(
        public readonly ?string $value,
        public readonly string $status,
        public readonly ?string $note = null,
    ) {}

    public static function clean(?string $value): self
    {
        return new self($value, 'clean');
    }

    public static function warning(?string $value, string $note): self
    {
        return new self($value, 'warning', $note);
    }

    public static function invalid(?string $value, string $note): self
    {
        return new self($value, 'invalid', $note);
    }

    public function severity(): int
    {
        return match ($this->status) {
            'invalid' => 2,
            'warning' => 1,
            default => 0,
        };
    }
}
