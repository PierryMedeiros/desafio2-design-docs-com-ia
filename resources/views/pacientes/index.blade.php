@extends('layouts.painel')

@section('titulo', 'Pacientes')

@section('conteudo')
    <h1>Pacientes</h1>

    <div class="buscas">
        <form method="GET" action="{{ route('pacientes.index') }}" class="filtros">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nome">
            <button type="submit">Buscar</button>
        </form>
        <form method="GET" action="{{ route('pacientes.busca') }}" class="filtros">
            <input type="text" name="cpf" placeholder="Buscar por CPF">
            <button type="submit">Buscar CPF</button>
        </form>
    </div>

    <table class="tabela">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Telefone</th>
                <th>E-mail</th>
                <th>WhatsApp</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pacientes as $paciente)
                <tr>
                    <td><a href="{{ route('pacientes.show', $paciente->id) }}">{{ $paciente->nome }}</a></td>
                    <td>{{ $paciente->telefone }}</td>
                    <td>{{ $paciente->email }}</td>
                    <td>{{ $paciente->aceita_whatsapp ? 'sim' : 'não' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="vazio">Nenhum paciente encontrado.</td>
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
        <label class="inline"><input type="checkbox" name="aceita_whatsapp" value="1" @checked(old('aceita_whatsapp'))> Aceita receber lembretes por WhatsApp</label>
        <button type="submit">Cadastrar</button>
    </form>
@endsection
