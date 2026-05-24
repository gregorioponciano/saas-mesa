# MESAS — SISTEMA DE GESTÃO DE MESAS E QR CODE

## 22. SISTEMA DE MESAS

### 22.1 Estados (Status Cycle)

```
free → occupied → reserved → free
```

| Status | Descrição | Cor no grid |
|--------|-----------|-------------|
| free | Livre | Verde |
| occupied | Ocupada | Vermelho |
| reserved | Reservada | Azul |

### 22.2 Operações CRUD

| Operação | Single | Bulk |
|----------|--------|------|
| Criar | Formulário com número, capacidade, status, observação | Prefixo + range (início/fim) + capacidade |
| Editar | Carregar → alterar → salvar | — |
| Excluir | Com proteção (impede se há pedidos ativos) | — |
| Ciclar status | free → occupied → reserved → free | — |

### 22.3 Limites por Plano

| Plano | max_tables |
|-------|------------|
| free | 2 |
| paid | 50 |

**Verificação**: `$tenant->canAddTable()` → `$this->tables()->count() < $this->max_tables`

### 22.4 Unique Constraint

`UNIQUE (tenant_id, number)` — cada tenant tem seus próprios números de mesa.

### 22.5 Proteção de Exclusão

```
Table@delete($id):
  → Se mesa tem orders com status ['novo', 'em_preparo', 'saiu_entrega']:
    → Recusa exclusão com mensagem
  → Senão: exclui mesa
```

---

## 23. SISTEMA DE QR CODE

### 23.1 Geração

| Tecnologia | endroid/qr-code ^6.1 |
|------------|---------------------|
| Writer | PngWriter |
| Encoding | UTF-8 |
| Size | 300px |
| Margin | 10px |
| Output | base64 PNG inline |

### 23.2 URL Gerada

```
{domain}/cardapio/{tenant-slug}?token={table-uuid}
```

### 23.3 Fluxo

```
Admin clica "QR Code" em uma mesa (TablesPage@showQrCode)
  → Busca Table com tenant
  → Gera URL: route('menu.show', ['slug' => $table->tenant->slug, 'token' => $table->token])
  → Gera QR Code via Endroid Builder
  → Armazena como data:image/png;base64,...
  → Exibe modal com QR + URL
```

### 23.4 Token UUID

- Gerado automaticamente no `creating` event do model Table
- Usa `Str::uuid()` do Laravel
- Único na tabela (UNIQUE constraint)
- Identifica a mesa na URL pública
