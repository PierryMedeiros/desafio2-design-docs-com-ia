@extends('layouts.painel')

@section('titulo', 'Novo agendamento')

@section('conteudo')
    <h1>Novo agendamento</h1>

    <form method="POST" action="{{ route('agendamentos.store') }}" class="formulario">
        @csrf
        <label>
            Paciente
            <select name="paciente_id" required>
                @foreach ($pacientes as $paciente)
                    <option value="{{ $paciente->id }}" {{ old('paciente_id') == $paciente->id ? 'selected' : '' }}>{{ $paciente->nome }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Profissional
            <select name="profissional_id" required>
                @foreach ($profissionais as $profissional)
                    <option value="{{ $profissional->id }}" {{ old('profissional_id') == $profissional->id ? 'selected' : '' }}>{{ $profissional->nome }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Serviço
            <select name="servico_id" required>
                @foreach ($servicos as $servico)
                    <option value="{{ $servico->id }}" {{ old('servico_id') == $servico->id ? 'selected' : '' }}>{{ $servico->nome }} ({{ $servico->duracao_minutos }} min)</option>
                @endforeach
            </select>
        </label>
        <label>
            Data
            <input type="date" name="data" value="{{ old('data', $data) }}" required>
        </label>
        <label>
            Hora
            <input type="time" name="hora" value="{{ old('hora') }}" required>
        </label>
        <label>
            Fim
            <input type="time" name="hora_fim" value="{{ old('hora_fim') }}" required>
        </label>
        <button type="submit">Agendar</button>
    </form>
@endsection
