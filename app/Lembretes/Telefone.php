<?php

namespace App\Lembretes;

class Telefone
{
    public static function e164(?string $telefone, string $ddi = '55', string $dddPadrao = '11'): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $telefone);

        if ($digitos === '') {
            return null;
        }

        if (strlen($digitos) <= 9) {
            $digitos = $dddPadrao.$digitos;
        }

        if (! str_starts_with($digitos, $ddi) || strlen($digitos) <= 11) {
            $digitos = $ddi.$digitos;
        }

        return '+'.$digitos;
    }
}
