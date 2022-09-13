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
                    <option value="{{ $paciente->id }}" @selected(old('paciente_id', $pacienteId) == $paciente->id)>{{ $paciente->nome }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Profissional
            <select name="profissional_id" required>
                @foreach ($profissionais as $profissional)
                    <option value="{{ $profissional->id }}" @selected(old('profissional_id') == $profissional->id)>{{ $profissional->nome }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Serviço
            <select name="servico_id" required>
                @foreach ($servicos as $servico)
                    <option value="{{ $servico->id }}" @selected(old('servico_id') == $servico->id)>{{ $servico->nome }} ({{ $servico->duracao_minutos }} min)</option>
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
            Link da teleconsulta
            <input type="url" name="link_teleconsulta" value="{{ old('link_teleconsulta') }}">
        </label>
        <label>
            Convênio
            <input type="text" name="convenio" value="{{ old('convenio') }}" maxlength="100">
        </label>
        <label>
            Notas clínicas
            <textarea name="notas_clinicas" rows="4">{{ old('notas_clinicas') }}</textarea>
        </label>
        <button type="submit">Agendar</button>
    </form>
@endsection
