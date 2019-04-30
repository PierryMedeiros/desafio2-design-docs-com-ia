@extends('layouts.painel')

@section('titulo', 'Agenda')

@section('conteudo')
    <div class="cabecalho">
        <h1>Agenda de {{ $data->format('d/m/Y') }}</h1>
        <a class="botao" href="{{ route('agendamentos.create', ['data' => $data->toDateString()]) }}">Novo agendamento</a>
    </div>

    <form method="GET" action="{{ route('agenda') }}" class="filtros">
        <input type="date" name="data" value="{{ $data->toDateString() }}">
        <button type="submit">Ir</button>
    </form>

    <table class="tabela agenda">
        <thead>
            <tr>
                <th>Horário</th>
                <th>Paciente</th>
                <th>Profissional</th>
                <th>Serviço</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($agendamentos as $agendamento)
                <tr>
                    <td>{{ $agendamento->inicio->format('H:i') }} – {{ $agendamento->fim->format('H:i') }}</td>
                    <td>
                        <a href="{{ route('pacientes.show', $agendamento->paciente_id) }}">{{ $agendamento->paciente->nome }}</a>
                    </td>
                    <td>{{ $agendamento->profissional->nome }}</td>
                    <td>{{ $agendamento->servico->nome }}</td>
                    <td>
                        <form method="POST" action="{{ route('agendamentos.status', $agendamento->id) }}" class="status-form">
                            @csrf
                            @method('PATCH')
                            <select name="status" onchange="this.form.submit()">
                                @foreach (\App\Models\Agendamento::STATUS as $status)
                                    <option value="{{ $status }}" {{ $agendamento->status === $status ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nenhum agendamento neste dia.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
