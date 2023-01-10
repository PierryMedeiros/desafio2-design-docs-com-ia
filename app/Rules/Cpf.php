<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Cpf implements Rule
{
    public function passes($attribute, $value)
    {
        return self::valido((string) $value);
    }

    public function message()
    {
        return 'O CPF informado não é válido.';
    }

    public static function valido(string $cpf): bool
    {
        $digitos = preg_replace('/\D/', '', $cpf);

        if (strlen($digitos) !== 11 || preg_match('/^(\d)\1{10}$/', $digitos)) {
            return false;
        }

        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $digitos[$i] * (($posicao + 1) - $i);
            }

            $digito = ((10 * $soma) % 11) % 10;

            if ((int) $digitos[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }
}
