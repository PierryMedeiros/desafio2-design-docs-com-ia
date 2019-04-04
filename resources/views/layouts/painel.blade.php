<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Painel') · Horalis</title>
    <link rel="stylesheet" href="{{ asset('css/painel.css') }}">
</head>
<body>
        <header class="topo">
            <a href="{{ url('/') }}" class="marca">Horalis</a>
            <nav>
            </nav>
        </header>

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
