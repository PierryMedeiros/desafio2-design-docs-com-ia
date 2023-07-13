<?php

namespace App\Http\Controllers;

use App\Anexos\ArmazenamentoAnexos;
use App\Models\Agendamento;
use App\Models\Anexo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnexoController extends Controller
{
    public function store(Request $request, int $id, ArmazenamentoAnexos $armazenamento): RedirectResponse
    {
        $agendamento = Agendamento::findOrFail($id);

        $request->validate([
            'arquivo' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,txt'],
        ]);

        $armazenamento->guardar($agendamento, $request->file('arquivo'));

        return back()->with('sucesso', 'Anexo enviado.');
    }

    public function show(int $id, ArmazenamentoAnexos $armazenamento): RedirectResponse
    {
        $anexo = Anexo::findOrFail($id);

        return redirect()->away($armazenamento->urlTemporaria($anexo));
    }
}
