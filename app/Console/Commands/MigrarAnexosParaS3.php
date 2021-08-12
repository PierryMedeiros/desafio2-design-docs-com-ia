<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Anexo;
use App\Models\Tenant;
use App\Tenancy\GerenciadorSchemas;

class MigrarAnexosParaS3 extends Command
{
    protected $signature = 'anexos:migrar-para-s3 {--dry-run}';

    protected $description = 'Copia os anexos do disco local para o S3 e atualiza o caminho (rodar uma vez)';

    public function handle(GerenciadorSchemas $schemas)
    {
        $local = Storage::disk('anexos');
        $s3 = Storage::disk('s3');
        $copiados = 0;
        $faltando = 0;

        foreach (Tenant::orderBy('id')->get() as $tenant) {
            $schemas->usar($tenant);

            Anexo::where('caminho', 'not like', 'tenants/%')->orderBy('id')->chunkById(200, function ($anexos) use ($tenant, $local, $s3, &$copiados, &$faltando) {
                foreach ($anexos as $anexo) {
                    if (!$local->exists($anexo->caminho)) {
                        $this->warn("[{$tenant->schema}] arquivo não encontrado: {$anexo->caminho}");
                        $faltando++;

                        continue;
                    }

                    $destino = sprintf(
                        'tenants/%d/agendamentos/%d/%s.%s',
                        $tenant->id,
                        $anexo->agendamento_id,
                        Str::uuid(),
                        pathinfo($anexo->caminho, PATHINFO_EXTENSION) ?: 'bin'
                    );

                    if (!$this->option('dry-run')) {
                        $s3->put($destino, $local->get($anexo->caminho), ['ContentType' => $anexo->tipo]);
                        $anexo->caminho = $destino;
                        $anexo->save();
                    }

                    $copiados++;
                }
            });
        }

        $schemas->usarPublico();

        $this->info("Copiados: {$copiados}. Não encontrados: {$faltando}.");
    }
}
