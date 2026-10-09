# Rascunhos gerados pela IA (não são ADRs)

Esta pasta guarda os artefatos intermediários gerados pelos plugins `adrs-management` durante a investigação. Eles **não foram revisados linha a linha** e contêm afirmações que eu não aceitei nas ADRs finais. Use-os só como registro do processo. As decisões valem pelo que está em `docs/adrs/ADR-*.md`.

- `mapping.md`: saída do `/adr-map --context-dir=contexto` (fase 1).
- `potential-adrs/` e `potential-adrs-index.md`: saída do `/adr-identify` (fase 2), com 8 agentes em paralelo, um por módulo. O índice foi sobrescrito várias vezes pelos agentes concorrentes e reconstruído por eles. Pode estar incompleto.
- `generated/`: dois testes do `/adr-generate` (fase 3), mantidos para comparação. Têm status traduzido, data em DD-MM-AAAA, nenhum hash e, no caso da AWS, motivos que as fontes não sustentam. Veja a seção "Erros da IA" no `README.md` da raiz.
