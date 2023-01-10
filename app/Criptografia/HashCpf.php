<?php

namespace App\Criptografia;

class HashCpf
{
    public static function gerar(?string $cpf): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $cpf);

        if ($digitos === '') {
            return null;
        }

        return hash_hmac('sha256', $digitos, (string) config('app.cpf_hash_key'));
    }
}
