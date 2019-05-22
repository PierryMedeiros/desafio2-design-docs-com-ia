<?php
namespace Tests\Unit;

use App\Models\Agendamento;
use Tests\TestCase;

class AgendamentoTest extends TestCase
{
    public function testStatusPadraoEAgendado()
    {
        $this->assertEquals('agendado', Agendamento::AGENDADO);
        $this->assertContains(Agendamento::CANCELADO, Agendamento::STATUS);
    }
}
