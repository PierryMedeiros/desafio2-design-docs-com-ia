@extends('layouts.painel')

@section('titulo', 'Profissionais')

@section('conteudo')
    <h1>Profissionais</h1>

    <table class="tabela">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Especialidade</th>
                <th>Registro</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($profissionais as $profissional)
                <tr>
                    <td>{{ $profissional->nome }}</td>
                    <td>{{ $profissional->especialidade }}</td>
                    <td>{{ $profissional->registro }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
