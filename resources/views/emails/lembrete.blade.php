<p>Olá, {{ $agendamento->paciente->nome }}!</p>

<p>
    Lembramos que sua consulta na {{ $tenant->nome }} está marcada para
    {{ $agendamento->inicio->format('d/m/Y') }} às {{ $agendamento->inicio->format('H:i') }},
    com {{ $agendamento->profissional->nome }}.
</p>

<p>Se não puder comparecer, avise a clínica.</p>
