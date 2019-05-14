<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Painel') · Horalis</title>
    <link rel="stylesheet" href="{{ asset('css/painel.css') }}">
</head>
<body>
    @auth
        <header class="topo">
            <a href="{{ route('agenda') }}" class="marca">Horalis</a>
            <nav>
                <a href="{{ route('agenda') }}">Agenda</a>
                <a href="{{ route('pacientes.index') }}">Pacientes</a>
                <a href="{{ route('profissionais.index') }}">Profissionais</a>
            </nav>
            <div class="usuario">
                <span>{{ auth()->user()->name }} · {{ auth()->user()->tenant->nome }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="link">Sair</button>
                </form>
            </div>
        </header>
    @endauth

    <main class="conteudo">
        @if (session('sucesso'))
            <div class="alerta sucesso">{{ session('sucesso') }}</div>
        @endif
        @if ($errors->any())
            <div class="alerta erro">
                @foreach ($errors->all() as $erro)
                    <div>{{ $erro }}</div>
                @endforeach
            </div>
        @endif

        @yield('conteudo')
    </main>
</body>
</html>
