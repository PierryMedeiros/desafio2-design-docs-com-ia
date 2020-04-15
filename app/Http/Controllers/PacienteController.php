<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paciente;

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

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:14',
            'telefone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'data_nascimento' => 'nullable|date',
        ]);

        Paciente::create($dados);

        return redirect()->route('pacientes.index')->with('sucesso', 'Paciente cadastrado.');
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
}
