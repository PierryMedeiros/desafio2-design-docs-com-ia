@extends('layouts.painel')

@section('titulo', 'Pacientes')

@section('conteudo')
    <h1>Pacientes</h1>

    <form method="GET" action="{{ route('pacientes.index') }}" class="filtros">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nome">
        <button type="submit">Buscar</button>
    </form>

    <table class="tabela">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Telefone</th>
                <th>E-mail</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pacientes as $paciente)
                <tr>
                    <td><a href="{{ route('pacientes.show', $paciente->id) }}">{{ $paciente->nome }}</a></td>
                    <td>{{ $paciente->telefone }}</td>
                    <td>{{ $paciente->email }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="vazio">Nenhum paciente encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $pacientes->links('pagination::simple-default') }}

    <h2>Novo paciente</h2>
    <form method="POST" action="{{ route('pacientes.store') }}" class="formulario">
        @csrf
        <label>Nome <input type="text" name="nome" value="{{ old('nome') }}" required></label>
        <label>CPF <input type="text" name="cpf" value="{{ old('cpf') }}" required></label>
        <label>Telefone <input type="text" name="telefone" value="{{ old('telefone') }}" required></label>
        <label>E-mail <input type="email" name="email" value="{{ old('email') }}"></label>
        <label>Data de nascimento <input type="date" name="data_nascimento" value="{{ old('data_nascimento') }}"></label>
        <label>Senha do app <input type="password" name="senha"></label>
        <button type="submit">Cadastrar</button>
    </form>
@endsection
