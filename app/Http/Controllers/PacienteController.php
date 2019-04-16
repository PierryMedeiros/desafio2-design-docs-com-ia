<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paciente;

class PacienteController extends Controller
{
    public function index()
    {
        $pacientes = Paciente::orderBy('nome')->get();

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
}
