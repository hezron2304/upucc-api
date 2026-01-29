<?php
namespace App\Core;

class Validator
{

    public static function validate(array $data, array $rules)
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);

            foreach ($rulesArray as $rule) {
                $value = $data[$field] ?? null;

                if ($rule === 'required' && empty($value)) {
                    $errors[$field][] = "$field wajib diisi";
                }

                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "Format email tidak valid";
                }

                if ($rule === 'numeric' && !is_numeric($value)) {
                    $errors[$field][] = "$field harus berupa angka";
                }
            }
        }

        if (!empty($errors)) {
            // Throw exception directly or return errors? 
            // Better throw exception to stop execution immediately
            Response::json('error', 'Validasi Gagal', $errors, 400);
        }

        return true;
    }
}
?>