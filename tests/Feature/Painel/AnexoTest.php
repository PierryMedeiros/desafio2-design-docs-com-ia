<?php
namespace Tests\Feature\Painel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Anexo;
use Tests\CriaClinica;
use Tests\TestCase;

class AnexoTest extends TestCase
{
    use CriaClinica, RefreshDatabase;

    public function test_upload_grava_no_s3_e_download_redireciona_para_url_temporaria(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->buildTemporaryUrlsUsing(fn ($caminho, $expiracao) => 'https://s3.test/'.$caminho.'?expira='.$expiracao->timestamp);

        $clinica = $this->criarClinica();
        $agendamento = $this->criarAgendamento(
            $clinica,
            $this->criarPaciente($clinica),
            $this->criarProfissional($clinica),
            $this->criarServico($clinica),
            $this->proximoDiaUtil()->setTime(9, 0)
        );
        $usuario = $this->criarUsuario($clinica);

        $this->actingAs($usuario)
            ->post("/agendamentos/{$agendamento->id}/anexos", [
                'arquivo' => UploadedFile::fake()->create('exame.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect();

        $anexo = Anexo::first();

        $this->assertSame('exame.pdf', $anexo->nome_original);
        $this->assertStringStartsWith("tenants/{$clinica->id}/agendamentos/{$agendamento->id}/", $anexo->caminho);
        Storage::disk('s3')->assertExists($anexo->caminho);

        $resposta = $this->actingAs($usuario)->get("/anexos/{$anexo->id}");

        $resposta->assertRedirect();
        $this->assertStringStartsWith('https://s3.test/'.$anexo->caminho, $resposta->headers->get('Location'));
    }
}
