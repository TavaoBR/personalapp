# 07 — Anamnese Configurável

## Form Builder

O Personal cria templates de anamnese.

Tipos:

```text
TEXT
LONG_TEXT
NUMBER
DATE
YES_NO
SINGLE_SELECT
MULTI_SELECT
SCALE
```

## Question Configuration

Usar configuração flexível para:

- options;
- min;
- max;
- placeholder;
- unidade;
- validações.

## Versionamento

```text
AnamnesisTemplate
└── AnamnesisTemplateVersion
     └── Questions
```

## Resposta

```text
StudentAnamnesis
└── StudentAnamnesisAnswer
```

Nova anamnese cria novo registro.

Nunca substituir a anterior.
