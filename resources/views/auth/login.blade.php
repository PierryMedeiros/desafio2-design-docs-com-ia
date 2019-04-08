@extends('layouts.painel')

@section('titulo', 'Entrar')

@section('conteudo')
    <div class="login">
        <h1>Horalis</h1>
        <form method="POST" action="{{ url('/login') }}" class="formulario">
            @csrf
            <label>
                E-mail
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
            <label>
                Senha
                <input type="password" name="password" required>
            </label>
            <label class="inline">
                <input type="checkbox" name="lembrar" value="1"> Manter conectado
            </label>
            <button type="submit">Entrar</button>
        </form>
    </div>
@endsection
