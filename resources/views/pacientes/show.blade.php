@extends('layouts.painel')

@section('titulo', $paciente->nome)

@section('conteudo')
    <div class="cabecalho">
        <h1>{{ $paciente->nome }}</h1>
        <a class="botao" href="{{ route('agendamentos.create', ['paciente_id' => $paciente->id]) }}">Agendar</a>
    </div>

    <dl class="ficha">
        <dt>CPF</dt>
        <dd>{{ $paciente->cpfFormatado() }}</dd>
        <dt>Telefone</dt>
        <dd>{{ $paciente->telefone }}</dd>
        <dt>E-mail</dt>
        <dd>{{ $paciente->email ?? '—' }}</dd>
        <dt>Nascimento</dt>
        <dd>{{ $paciente->data_nascimento?->format('d/m/Y') ?? '—' }}</dd>
        <dt>WhatsApp</dt>
        <dd>{{ $paciente->aceita_whatsapp ? 'aceita lembretes' : 'não aceita' }}</dd>
    </dl>

    <h2>Histórico</h2>
    <table class="tabela">
        <thead>
            <tr>
                <th>Data</th>
                <th>Profissional</th>
                <th>Serviço</th>
                <th>Status</th>
                <th>Notas</th>
                <th>Anexos</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($agendamentos as $agendamento)
                <tr class="linha-{{ $agendamento->status }}">
                    <td>{{ $agendamento->inicio->format('d/m/Y H:i') }}</td>
                    <td>{{ $agendamento->profissional->nome }}</td>
                    <td>{{ $agendamento->servico->nome }}</td>
                    <td><span class="status status-{{ $agendamento->status }}">{{ $agendamento->status }}</span></td>
                    <td>{{ $agendamento->notas_clinicas }}</td>
                    <td>
                        @foreach ($agendamento->anexos as $anexo)
                            <a href="{{ route('anexos.show', $anexo->id) }}">{{ $anexo->nome_original }}</a><br>
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="vazio">Nenhuma consulta ainda.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
