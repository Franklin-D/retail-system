<?php
// backend/utils/Validator.php

class Validator {
    public static function validate(array $data, array $rules): array {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $ruleList = explode('|', $ruleString);

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && (is_null($value) || trim((string)$value) === '')) {
                    $errors[$field][] = "El campo {$field} es obligatorio.";
                }

                if ($rule === 'numeric' && !is_null($value) && $value !== '' && !is_numeric($value)) {
                    $errors[$field][] = "El campo {$field} debe ser numérico.";
                }

                if (str_starts_with($rule, 'min:') && !is_null($value) && $value !== '') {
                    $min = (float) str_replace('min:', '', $rule);
                    if ((float)$value < $min) {
                        $errors[$field][] = "El campo {$field} debe ser mayor o igual a {$min}.";
                    }
                }
            }
        }

        return $errors;
    }
}