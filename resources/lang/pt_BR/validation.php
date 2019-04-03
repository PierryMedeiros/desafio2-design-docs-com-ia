<?php

return [

    'after_or_equal' => 'O campo :attribute deve ser uma data igual ou posterior a :date.',
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'date' => 'O campo :attribute não é uma data válida.',
    'date_format' => 'O campo :attribute não está no formato :format.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'exists' => 'O :attribute selecionado é inválido.',
    'file' => 'O campo :attribute deve ser um arquivo.',
    'in' => 'O :attribute selecionado é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'max' => [
        'file' => 'O arquivo :attribute não pode ter mais de :max kilobytes.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'mimes' => 'O arquivo :attribute deve ser do tipo: :values.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser pelo menos :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Já existe um cadastro com esse :attribute.',
    'url' => 'O campo :attribute deve ser uma URL válida.',

    'attributes' => [
        'arquivo' => 'arquivo',
        'data' => 'data',
        'data_nascimento' => 'data de nascimento',
        'email' => 'e-mail',
        'hora' => 'hora',
        'inicio' => 'início',
        'link_teleconsulta' => 'link da teleconsulta',
        'paciente_id' => 'paciente',
        'password' => 'senha',
        'profissional_id' => 'profissional',
        'servico_id' => 'serviço',
        'telefone' => 'telefone',
    ],

];
