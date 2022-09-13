@extends('layouts.painel')

@section('titulo', 'Agenda')

@section('conteudo')
    <div class="cabecalho">
        <h1>Agenda de {{ $data->format('d/m/Y') }}</h1>
        <a class="botao" href="{{ route('agendamentos.create', ['data' => $data->toDateString()]) }}">Novo agendamento</a>
    </div>

    <form method="GET" action="{{ route('agenda') }}" class="filtros">
        <a class="botao secundario" href="{{ route('agenda', ['data' => $data->subDay()->toDateString(), 'profissional_id' => $profissionalId]) }}">&larr;</a>
        <input type="date" name="data" value="{{ $data->toDateString() }}">
        <a class="botao secundario" href="{{ route('agenda', ['data' => $data->addDay()->toDateString(), 'profissional_id' => $profissionalId]) }}">&rarr;</a>
        <select name="profissional_id">
            <option value="">Todos os profissionais</option>
            @foreach ($profissionais as $profissional)
                <option value="{{ $profissional->id }}" @selected($profissionalId === $profissional->id)>{{ $profissional->nome }}</option>
            @endforeach
        </select>
        <button type="submit">Filtrar</button>
    </form>

    <table class="tabela agenda">
        <thead>
            <tr>
                <th>Horário</th>
                <th>Paciente</th>
                <th>Profissional</th>
                <th>Serviço</th>
                <th>Status</th>
                <th>Anexos</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($agendamentos as $agendamento)
                <tr class="linha-{{ $agendamento->status }}">
                    <td>{{ $agendamento->inicio->format('H:i') }} – {{ $agendamento->fim->format('H:i') }}</td>
                    <td>
                        <a href="{{ route('pacientes.show', $agendamento->paciente_id) }}">{{ $agendamento->paciente->nome }}</a>
                        @if ($agendamento->link_teleconsulta)
                            <a class="teleconsulta" href="{{ $agendamento->link_teleconsulta }}" target="_blank" rel="noopener">teleconsulta</a>
                        @endif
                    </td>
                    <td>{{ $agendamento->profissional->nome }}</td>
                    <td>{{ $agendamento->servico->nome }}</td>
                    <td>
                        <form method="POST" action="{{ route('agendamentos.status', $agendamento->id) }}" class="status-form">
                            @csrf
                            @method('PATCH')
                            <select name="status" onchange="if (this.value !== 'cancelado' || confirm('Cancelar este agendamento?')) this.form.submit()">
                                @foreach (\App\Models\Agendamento::STATUS as $status)
                                    <option value="{{ $status }}" @selected($agendamento->status === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td>
                        @foreach ($agendamento->anexos as $anexo)
                            <a href="{{ route('anexos.show', $anexo->id) }}">{{ $anexo->nome_original }}</a><br>
                        @endforeach
                        <form method="POST" action="{{ route('anexos.store', $agendamento->id) }}" enctype="multipart/form-data" class="anexo-form">
                            @csrf
                            <input type="file" name="arquivo" required>
                            <button type="submit">Enviar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="vazio">Nenhum agendamento neste dia.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
