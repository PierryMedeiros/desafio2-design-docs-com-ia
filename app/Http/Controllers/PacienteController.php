<?php

namespace App\Http\Controllers;

use App\Criptografia\HashCpf;
use App\Models\Paciente;
use App\Rules\Cpf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PacienteController extends Controller
{
    public function index(Request $request): View
    {
        $pacientes = Paciente::query()
            ->when($request->filled('q'), fn ($query) => $query->where('nome', 'ilike', '%'.$request->input('q').'%'))
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return view('pacientes.index', ['pacientes' => $pacientes]);
    }

    public function show(int $id): View
    {
        $paciente = Paciente::findOrFail($id);

        $agendamentos = $paciente->agendamentos()
            ->with(['profissional', 'servico', 'anexos'])
            ->orderByDesc('inicio')
            ->get();

        return view('pacientes.show', [
            'paciente' => $paciente,
            'agendamentos' => $agendamentos,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->tenant()->id;

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['required', new Cpf],
            'telefone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', Rule::unique('pacientes')->where('tenant_id', $tenantId)],
            'data_nascimento' => ['nullable', 'date'],
            'senha' => ['nullable', 'string', 'min:6'],
            'aceita_whatsapp' => ['nullable', 'boolean'],
        ]);

        if (Paciente::where('cpf_hash', HashCpf::gerar($dados['cpf']))->exists()) {
            return back()->withInput()->withErrors(['cpf' => 'Já existe um paciente com esse CPF.']);
        }

        $paciente = Paciente::create([
            ...$dados,
            'aceita_whatsapp' => $request->boolean('aceita_whatsapp'),
        ]);

        return redirect()->route('pacientes.show', $paciente->id)->with('sucesso', 'Paciente cadastrado.');
    }

    public function busca(Request $request): RedirectResponse
    {
        $request->validate(['cpf' => ['required', 'string']]);

        $paciente = Paciente::where('cpf_hash', HashCpf::gerar($request->input('cpf')))->first();

        if (! $paciente) {
            return redirect()->route('pacientes.index')->with('aviso', 'Nenhum paciente com esse CPF.');
        }

        return redirect()->route('pacientes.show', $paciente->id);
    }
}
