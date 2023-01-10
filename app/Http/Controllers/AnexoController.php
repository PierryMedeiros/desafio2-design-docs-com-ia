<?php

namespace App\Http\Controllers;

use App\Anexos\ArmazenamentoAnexos;
use App\Models\Agendamento;
use App\Models\Anexo;
use Illuminate\Http\Request;

class AnexoController extends Controller
{
    public function store(Request $request, $id, ArmazenamentoAnexos $armazenamento)
    {
        $agendamento = Agendamento::findOrFail($id);

        $request->validate([
            'arquivo' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,txt',
        ]);

        $armazenamento->guardar($agendamento, $request->file('arquivo'));

        return back()->with('sucesso', 'Anexo enviado.');
    }

    public function show($id, ArmazenamentoAnexos $armazenamento)
    {
        $anexo = Anexo::findOrFail($id);

        return redirect()->away($armazenamento->urlTemporaria($anexo));
    }
}
