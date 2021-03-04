<?php
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\Tenant;
use App\Tenancy\GerenciadorSchemas;

class AuthController extends Controller
{
    public function store(Request $request, GerenciadorSchemas $schemas)
    {
        $dados = $request->validate([
            'email' => 'required|email',
            'senha' => 'required|string',
            'clinica' => 'required|string',
            'dispositivo' => 'nullable|string|max:100',
        ]);

        $tenant = Tenant::where('slug', $dados['clinica'])->first();

        if (!$tenant) {
            return response()->json(['message' => 'Credenciais inválidas.'], 422);
        }

        $schemas->usar($tenant);

        $paciente = Paciente::where('email', $dados['email'])->first();

        if (!$paciente || !$paciente->senha || !Hash::check($dados['senha'], $paciente->senha)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 422);
        }

        $token = $paciente->createToken($dados['dispositivo'] ?? 'app');

        return response()->json([
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'paciente' => [
                'id' => $paciente->id,
                'nome' => $paciente->nome,
            ],
        ], 201);
    }
}
