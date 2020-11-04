@extends('layouts.painel')

@section('titulo', 'Profissionais')

@section('conteudo')
    <h1>Profissionais</h1>

    @php($dias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'])

    <table class="tabela">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Especialidade</th>
                <th>Registro</th>
                <th>Disponibilidade</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($profissionais as $profissional)
                <tr class="{{ $profissional->ativo ? '' : 'inativo' }}">
                    <td>{{ $profissional->nome }}</td>
                    <td>{{ $profissional->especialidade }}</td>
                    <td>{{ $profissional->registro }}</td>
                    <td>
                        @foreach ($profissional->disponibilidades as $faixa)
                            {{ $dias[$faixa->dia_semana] }} {{ substr($faixa->hora_inicio, 0, 5) }}–{{ substr($faixa->hora_fim, 0, 5) }}<br>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
