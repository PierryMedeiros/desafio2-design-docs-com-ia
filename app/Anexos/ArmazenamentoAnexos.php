<?php
namespace App\Anexos;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Agendamento;
use App\Models\Anexo;

class ArmazenamentoAnexos
{
    public const DISCO = 's3';

    public const VALIDADE_MINUTOS = 10;

    public function guardar(Agendamento $agendamento, UploadedFile $arquivo): Anexo
    {
        $caminho = sprintf(
            'tenants/%d/agendamentos/%d/%s.%s',
            $agendamento->tenant_id,
            $agendamento->id,
            Str::uuid(),
            $arquivo->getClientOriginalExtension() ?: 'bin'
        );

        Storage::disk(self::DISCO)->put($caminho, $arquivo->get(), [
            'ContentType' => $arquivo->getMimeType(),
        ]);

        return $agendamento->anexos()->create([
            'tenant_id' => $agendamento->tenant_id,
            'caminho' => $caminho,
            'nome_original' => $arquivo->getClientOriginalName(),
            'tipo' => $arquivo->getMimeType(),
            'tamanho' => $arquivo->getSize(),
        ]);
    }

    public function urlTemporaria(Anexo $anexo): string
    {
        return $this->discoParaUrls()->temporaryUrl(
            $anexo->caminho,
            now()->addMinutes(self::VALIDADE_MINUTOS)
        );
    }

    private function discoParaUrls(): Filesystem
    {
        $config = config('filesystems.disks.'.self::DISCO);

        if (empty($config['endpoint_publico'])) {
            return Storage::disk(self::DISCO);
        }

        return Storage::build(array_merge($config, ['endpoint' => $config['endpoint_publico']]));
    }
}
