<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Lembretes\Telefone;

class TelefoneTest extends TestCase
{
    public function testFormataEmE164()
    {
        $this->assertSame('+5511987654321', Telefone::e164('(11) 98765-4321'));
        $this->assertSame('+5511987654321', Telefone::e164('+55 11 98765-4321'));
        $this->assertSame('+5511987654321', Telefone::e164('98765-4321'));
        $this->assertSame('+5555999998888', Telefone::e164('(55) 99999-8888'));
        $this->assertNull(Telefone::e164(''));
    }
}
