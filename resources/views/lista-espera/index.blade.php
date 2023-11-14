@extends('layouts.painel')

@section('titulo', 'Lista de espera')

@section('conteudo')
    <h1>Lista de espera</h1>

    <table class="tabela">
        <thead>
            <tr>
                <th>Entrada</th>
                <th>Paciente</th>
                <th>Profissional</th>
                <th>Serviço</th>
                <th>Data desejada</th>
                <th>Observação</th>
                <th>Avisado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entradas as $entrada)
                <tr>
                    <td>{{ $entrada->created_at->format('d/m/Y') }}</td>
                    <td><a href="{{ route('pacientes.show', $entrada->paciente_id) }}">{{ $entrada->paciente->nome }}</a></td>
                    <td>{{ $entrada->profissional?->nome ?? 'qualquer' }}</td>
                    <td>{{ $entrada->servico?->nome ?? '—' }}</td>
                    <td>{{ $entrada->data_desejada->format('d/m/Y') }}</td>
                    <td>{{ $entrada->observacao }}</td>
                    <td>{{ $entrada->avisado_em?->format('d/m H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="vazio">Ninguém na lista de espera.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
