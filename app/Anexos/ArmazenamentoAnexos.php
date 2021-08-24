<?php
namespace App\Anexos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Agendamento;
use App\Models\Anexo;
use App\Tenancy\GerenciadorSchemas;

class ArmazenamentoAnexos
{
    const DISCO = 's3';

    const VALIDADE_MINUTOS = 10;

    public function guardar(Agendamento $agendamento, UploadedFile $arquivo)
    {
        $caminho = sprintf(
            'tenants/%d/agendamentos/%d/%s.%s',
            app(GerenciadorSchemas::class)->atual()->id,
            $agendamento->id,
            Str::uuid(),
            $arquivo->getClientOriginalExtension() ?: 'bin'
        );

        Storage::disk(self::DISCO)->put($caminho, $arquivo->get(), [
            'ContentType' => $arquivo->getMimeType(),
        ]);

        return $agendamento->anexos()->create([
            'caminho' => $caminho,
            'nome_original' => $arquivo->getClientOriginalName(),
            'tipo' => $arquivo->getMimeType(),
            'tamanho' => $arquivo->getSize(),
        ]);
    }

    public function urlTemporaria(Anexo $anexo)
    {
        return $this->discoParaUrls()->temporaryUrl(
            $anexo->caminho,
            now()->addMinutes(self::VALIDADE_MINUTOS)
        );
    }

    private function discoParaUrls()
    {
        $config = config('filesystems.disks.'.self::DISCO);

        if (empty($config['endpoint_publico'])) {
            return Storage::disk(self::DISCO);
        }

        return Storage::createS3Driver(array_merge($config, ['endpoint' => $config['endpoint_publico']]));
    }
}
