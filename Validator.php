<?php
/**
 * Fluent input validator.
 *
 * Usage:
 *   $v = new Validator($data);
 *   $v->required('title')->length('title', 3, 150);
 *   if ($v->fails()) { Response::error('Validation failed', 422, ['errors' => $v->errors()]); }
 */
declare(strict_types=1);

namespace Findly\Utils;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function value(string $field, $default = null)
    {
        return $this->data[$field] ?? $default;
    }

    public function required(string $field, string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        $value = $this->data[$field] ?? null;
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->errors[$field] = ucfirst($label) . ' is required';
        }
        return $this;
    }

    public function length(string $field, int $min, int $max, string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        $value = $this->data[$field] ?? '';
        $len = mb_strlen((string) $value);
        if ($len < $min || $len > $max) {
            $this->errors[$field] = ucfirst($label) . ' must be between ' . $min . ' and ' . $max . ' characters';
        }
        return $this;
    }

    public function email(string $field, string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'A valid ' . $label . ' is required';
        }
        return $this;
    }

    public function enum(string $field, array $allowed, string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = ucfirst($label) . ' must be one of: ' . implode(', ', $allowed);
        }
        return $this;
    }

    public function date(string $field, string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        $value = $this->data[$field] ?? '';
        if ($value !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', (string) $value);
            if (!$d || $d->format('Y-m-d') !== (string) $value) {
                $this->errors[$field] = 'A valid ' . $label . ' (YYYY-MM-DD) is required';
            }
        }
        return $this;
    }

    public function int(string $field, string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !ctype_digit((string) $value)) {
            $this->errors[$field] = ucfirst($label) . ' must be a number';
        }
        return $this;
    }

    public function phone(string $field, string $label = null): self
    {
        $label = $label ?? str_replace('_', ' ', $field);
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', (string) $value)) {
            $this->errors[$field] = 'A valid ' . $label . ' is required';
        }
        return $this;
    }

    public function add(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
