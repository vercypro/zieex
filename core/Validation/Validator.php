<?php
declare(strict_types=1);

namespace Zieex\Validation;

class ValidationException extends \RuntimeException
{
    public function __construct(private array $errors)
    {
        parent::__construct('Validation failed.');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

class Validator
{
    private array $data;
    private array $rules;
    private array $errors   = [];
    private array $validated = [];

    public function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
    }

    public function validate(): array
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                [$ruleName, $ruleParam] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $value, $ruleName, $ruleParam);
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $this->cast($field, $value);
            }
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }

        return $this->validated;
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): void
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->addError($field, "The {$field} field is required.");
                }
                break;

            case 'email':
                if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$field} must be a valid email address.");
                }
                break;

            case 'min':
                if ($value !== null && strlen((string)$value) < (int)$param) {
                    $this->addError($field, "The {$field} must be at least {$param} characters.");
                }
                break;

            case 'max':
                if ($value !== null && strlen((string)$value) > (int)$param) {
                    $this->addError($field, "The {$field} may not be greater than {$param} characters.");
                }
                break;

            case 'numeric':
                if ($value !== null && !is_numeric($value)) {
                    $this->addError($field, "The {$field} must be a number.");
                }
                break;

            case 'integer':
                if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "The {$field} must be an integer.");
                }
                break;

            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, "The {$field} must be a string.");
                }
                break;

            case 'boolean':
                if ($value !== null && !in_array($value, [true, false, 0, 1, '0', '1'], true)) {
                    $this->addError($field, "The {$field} must be true or false.");
                }
                break;

            case 'url':
                if ($value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "The {$field} must be a valid URL.");
                }
                break;

            case 'in':
                $allowed = explode(',', $param ?? '');
                if ($value !== null && !in_array($value, $allowed, true)) {
                    $this->addError($field, "The {$field} must be one of: {$param}.");
                }
                break;

            case 'not_in':
                $disallowed = explode(',', $param ?? '');
                if ($value !== null && in_array($value, $disallowed, true)) {
                    $this->addError($field, "The {$field} contains an invalid value.");
                }
                break;

            case 'confirmed':
                $confirmation = $this->data[$field . '_confirmation'] ?? null;
                if ($value !== $confirmation) {
                    $this->addError($field, "The {$field} confirmation does not match.");
                }
                break;

            case 'unique':
                [$table, $column] = explode(',', $param ?? '', 2) + [null, $field];
                if ($value !== null && $table) {
                    $exists = \Zieex\Database\DB::table($table)->where($column, $value)->exists();
                    if ($exists) {
                        $this->addError($field, "The {$field} has already been taken.");
                    }
                }
                break;

            case 'exists':
                [$table, $column] = explode(',', $param ?? '', 2) + [null, $field];
                if ($value !== null && $table) {
                    $exists = \Zieex\Database\DB::table($table)->where($column, $value)->exists();
                    if (!$exists) {
                        $this->addError($field, "The selected {$field} is invalid.");
                    }
                }
                break;

            case 'nullable':
                // Pass through - just allows null
                break;

            case 'regex':
                if ($value !== null && !preg_match($param, (string)$value)) {
                    $this->addError($field, "The {$field} format is invalid.");
                }
                break;

            case 'date':
                if ($value !== null && strtotime((string)$value) === false) {
                    $this->addError($field, "The {$field} must be a valid date.");
                }
                break;

            case 'array':
                if ($value !== null && !is_array($value)) {
                    $this->addError($field, "The {$field} must be an array.");
                }
                break;

            case 'json':
                if ($value !== null) {
                    json_decode((string)$value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->addError($field, "The {$field} must be valid JSON.");
                    }
                }
                break;
        }
    }

    private function cast(string $field, mixed $value): mixed
    {
        $rules = explode('|', $this->rules[$field]);
        foreach ($rules as $rule) {
            [$ruleName] = explode(':', $rule, 2);
            $value = match ($ruleName) {
                'integer', 'numeric' => $value !== null ? (int) $value : $value,
                'boolean'            => $value !== null ? (bool) $value : $value,
                'array'              => is_string($value) ? json_decode($value, true) : $value,
                default              => $value,
            };
        }
        return $value;
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        try {
            $this->validate();
            return false;
        } catch (ValidationException) {
            return true;
        }
    }
}
