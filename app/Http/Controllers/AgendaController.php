<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Agendamento;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $data = Carbon::parse($request->input('data', date('Y-m-d')));

        $agendamentos = Agendamento::whereDate('inicio', $data->toDateString())
            ->orderBy('inicio')
            ->get();

        return view('agenda.index', [
            'data' => $data,
            'agendamentos' => $agendamentos,
        ]);
    }
}
