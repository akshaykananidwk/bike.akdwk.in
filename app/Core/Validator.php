<?php
namespace App\Core;

/**
 * Lightweight input validator. Rules are pipe-delimited strings, e.g.
 *   'name' => 'required|min:2|max:120'
 *   'mobile' => 'required|mobile'
 *   'email' => 'nullable|email'
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function passes(): bool
    {
        foreach ($this->rules as $field => $ruleStr) {
            $value = $this->data[$field] ?? null;
            $rules = explode('|', $ruleStr);
            $nullable = in_array('nullable', $rules, true);

            if ($nullable && ($value === null || $value === '')) {
                continue;
            }
            foreach ($rules as $rule) {
                if ($rule === 'nullable') { continue; }
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $value, $name, $param);
            }
        }
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $errs) {
            return $errs[0] ?? null;
        }
        return null;
    }

    private function addError(string $field, string $msg): void
    {
        $this->errors[$field][] = $msg;
    }

    private function applyRule(string $field, $value, string $name, ?string $param): void
    {
        $label = ucwords(str_replace('_', ' ', $field));
        switch ($name) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                    $this->addError($field, "{$label} is required.");
                }
                break;
            case 'min':
                if (is_string($value) && mb_strlen($value) < (int)$param) {
                    $this->addError($field, "{$label} must be at least {$param} characters.");
                }
                break;
            case 'max':
                if (is_string($value) && mb_strlen($value) > (int)$param) {
                    $this->addError($field, "{$label} must not exceed {$param} characters.");
                }
                break;
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "{$label} must be a valid email.");
                }
                break;
            case 'mobile':
                if (!preg_match('/^[6-9]\d{9}$/', (string)$value)) {
                    $this->addError($field, "{$label} must be a valid 10-digit mobile number.");
                }
                break;
            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, "{$label} must be a number.");
                }
                break;
            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "{$label} must be an integer.");
                }
                break;
            case 'min_val':
                if (is_numeric($value) && $value + 0 < (float)$param) {
                    $this->addError($field, "{$label} must be at least {$param}.");
                }
                break;
            case 'in':
                $allowed = explode(',', (string)$param);
                if (!in_array((string)$value, $allowed, true)) {
                    $this->addError($field, "{$label} is invalid.");
                }
                break;
            case 'ifsc':
                if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper((string)$value))) {
                    $this->addError($field, "{$label} must be a valid IFSC code.");
                }
                break;
            case 'confirmed':
                if (($this->data[$field . '_confirmation'] ?? null) !== $value) {
                    $this->addError($field, "{$label} confirmation does not match.");
                }
                break;
            case 'date':
                if (strtotime((string)$value) === false) {
                    $this->addError($field, "{$label} must be a valid date.");
                }
                break;
        }
    }
}
