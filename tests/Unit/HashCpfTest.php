<?php

namespace Tests\Unit;

use App\Criptografia\HashCpf;
use App\Rules\Cpf;
use Tests\TestCase;

class HashCpfTest extends TestCase
{
    public function test_hash_ignora_pontuacao(): void
    {
        $this->assertSame(HashCpf::gerar('529.982.247-25'), HashCpf::gerar('52998224725'));
        $this->assertSame(64, strlen(HashCpf::gerar('52998224725')));
    }

    public function test_hash_depende_da_chave(): void
    {
        $antes = HashCpf::gerar('52998224725');

        config(['app.cpf_hash_key' => 'outra-chave']);

        $this->assertNotSame($antes, HashCpf::gerar('52998224725'));
    }

    public function test_cpf_vazio_nao_tem_hash(): void
    {
        $this->assertNull(HashCpf::gerar(''));
        $this->assertNull(HashCpf::gerar(null));
    }

    public function test_validacao_dos_digitos_do_cpf(): void
    {
        $this->assertTrue(Cpf::valido('529.982.247-25'));
        $this->assertFalse(Cpf::valido('529.982.247-26'));
        $this->assertFalse(Cpf::valido('111.111.111-11'));
    }
}
