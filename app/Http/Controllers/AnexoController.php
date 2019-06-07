<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Agendamento;
use App\Models\Anexo;

class AnexoController extends Controller
{
    public function store(Request $request, $id)
    {
        $agendamento = Agendamento::findOrFail($id);

        $request->validate([
            'arquivo' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,txt',
        ]);

        $arquivo = $request->file('arquivo');
        $caminho = $arquivo->store('agendamentos/'.$agendamento->id, 'anexos');

        $agendamento->anexos()->create([
            'caminho' => $caminho,
            'nome_original' => $arquivo->getClientOriginalName(),
            'tipo' => $arquivo->getMimeType(),
            'tamanho' => $arquivo->getSize(),
        ]);

        return back()->with('sucesso', 'Anexo enviado.');
    }

    public function show($id)
    {
        $anexo = Anexo::findOrFail($id);

        return Storage::disk('anexos')->download($anexo->caminho, $anexo->nome_original);
    }
}
