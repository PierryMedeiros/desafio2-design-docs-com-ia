<?php

namespace App\Lembretes;

class Telefone
{
    public static function e164($telefone, $ddi = '55', $dddPadrao = '11')
    {
        $digitos = preg_replace('/\D/', '', (string) $telefone);

        if ($digitos === '') {
            return null;
        }

        if (strlen($digitos) <= 9) {
            $digitos = $dddPadrao.$digitos;
        }

        if (substr($digitos, 0, strlen($ddi)) !== $ddi || strlen($digitos) <= 11) {
            $digitos = $ddi.$digitos;
        }

        return '+'.$digitos;
    }
}
