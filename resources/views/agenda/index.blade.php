@extends('layouts.painel')

@section('titulo', 'Agenda')

@section('conteudo')
    <h1>Agenda de {{ $data->format('d/m/Y') }}</h1>

    <table class="tabela">
        @foreach ($agendamentos as $agendamento)
            <tr>
                <td>{{ $agendamento->inicio->format('H:i') }}</td>
                <td>{{ $agendamento->paciente->nome }}</td>
                <td>{{ $agendamento->profissional->nome }}</td>
            </tr>
        @endforeach
    </table>
@endsection
