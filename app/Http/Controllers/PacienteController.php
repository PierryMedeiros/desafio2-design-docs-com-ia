<?php

namespace App\Http\Controllers;

use App\Criptografia\HashCpf;
use App\Models\Paciente;
use App\Rules\Cpf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    public function index(Request $request)
    {
        $pacientes = Paciente::query()
            ->when($request->input('q'), function ($query, $termo) {
                return $query->where('nome', 'ilike', '%'.$termo.'%');
            })
            ->orderBy('nome')
            ->paginate(20)
            ->appends($request->only('q'));

        return view('pacientes.index', ['pacientes' => $pacientes]);
    }

    public function show($id)
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

    public function store(Request $request)
    {
        $tenantId = $this->tenant()->id;

        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => ['required', new Cpf],
            'telefone' => 'required|string|max:20',
            'email' => ['nullable', 'email', Rule::unique('pacientes')->where('tenant_id', $tenantId)],
            'data_nascimento' => 'nullable|date',
            'senha' => 'nullable|string|min:6',
        ]);

        if (Paciente::where('cpf_hash', HashCpf::gerar($dados['cpf']))->exists()) {
            return back()->withInput()->withErrors(['cpf' => 'Já existe um paciente com esse CPF.']);
        }

        if (! empty($dados['senha'])) {
            $dados['senha'] = Hash::make($dados['senha']);
        }

        $paciente = Paciente::create($dados);

        return redirect()->route('pacientes.show', $paciente->id)->with('sucesso', 'Paciente cadastrado.');
    }

    public function busca(Request $request)
    {
        $request->validate(['cpf' => 'required|string']);

        $paciente = Paciente::where('cpf_hash', HashCpf::gerar($request->input('cpf')))->first();

        if (! $paciente) {
            return redirect()->route('pacientes.index')->with('aviso', 'Nenhum paciente com esse CPF.');
        }

        return redirect()->route('pacientes.show', $paciente->id);
    }
}
