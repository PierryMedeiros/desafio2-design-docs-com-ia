<?php

namespace Tests\Unit;

use App\Lembretes\CanalComFallback;
use App\Lembretes\CanalLembrete;
use App\Lembretes\FalhaNoEnvioDoLembrete;
use App\Models\Paciente;
use ArrayObject;
use RuntimeException;
use Tests\TestCase;

class CanalComFallbackTest extends TestCase
{
    private function canal(string $nome, bool $aceita, bool $falha, ArrayObject $registro): CanalLembrete
    {
        return new class($nome, $aceita, $falha, $registro) implements CanalLembrete
        {
            public function __construct(private string $nome, private bool $aceita, private bool $falha, private ArrayObject $registro) {}

            public function nome(): string
            {
                return $this->nome;
            }

            public function aceita(Paciente $paciente): bool
            {
                return $this->aceita;
            }

            public function enviar(Paciente $paciente, string $mensagem): void
            {
                if ($this->falha) {
                    throw new RuntimeException('fora do ar');
                }

                $this->registro[] = $this->nome;
            }
        };
    }

    public function test_usa_o_primeiro_canal_que_aceita(): void
    {
        $registro = new ArrayObject;
        $canal = new CanalComFallback([
            $this->canal('whatsapp', true, false, $registro),
            $this->canal('sms', true, false, $registro),
        ]);

        $canal->enviar(new Paciente, 'oi');

        $this->assertSame(['whatsapp'], $registro->getArrayCopy());
    }

    public function test_pula_canal_que_nao_aceita(): void
    {
        $registro = new ArrayObject;
        $canal = new CanalComFallback([
            $this->canal('whatsapp', false, false, $registro),
            $this->canal('sms', true, false, $registro),
        ]);

        $canal->enviar(new Paciente, 'oi');

        $this->assertSame(['sms'], $registro->getArrayCopy());
    }

    public function test_cai_para_o_proximo_quando_o_canal_falha(): void
    {
        $registro = new ArrayObject;
        $canal = new CanalComFallback([
            $this->canal('whatsapp', true, true, $registro),
            $this->canal('sms', true, false, $registro),
        ]);

        $canal->enviar(new Paciente, 'oi');

        $this->assertSame(['sms'], $registro->getArrayCopy());
    }

    public function test_lanca_excecao_quando_todos_falham(): void
    {
        $registro = new ArrayObject;
        $canal = new CanalComFallback([
            $this->canal('whatsapp', true, true, $registro),
            $this->canal('sms', true, true, $registro),
        ]);

        $this->expectException(FalhaNoEnvioDoLembrete::class);

        $canal->enviar(new Paciente, 'oi');
    }
}
