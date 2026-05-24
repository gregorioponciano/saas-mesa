<?php

namespace App\Livewire\Admin;

use App\Models\Table;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class TablesPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public int $editingTableId = 0;
    public string $number = '';
    public int $capacity = 4;
    public string $status = 'free';
    public string $observation = '';
    public bool $showForm = false;
    public string $formMode = 'single';

    public string $bulkPrefix = 'Mesa ';
    public int $bulkStart = 1;
    public int $bulkEnd = 10;
    public int $bulkCapacity = 4;

    public bool $showQr = false;
    public ?int $qrTableId = null;
    public ?string $qrTableNumber = null;
    public string $qrUrl = '';
    public string $qrImage = '';

    protected function rules(): array
    {
        if ($this->formMode === 'single') {
            return [
                'number' => 'required|string|max:20',
                'capacity' => 'required|integer|min:1|max:50',
                'status' => 'required|in:free,occupied,reserved',
                'observation' => 'nullable|string|max:500',
            ];
        }

        return [
            'bulkPrefix' => 'nullable|string|max:50',
            'bulkStart' => 'required|integer|min:1|max:999',
            'bulkEnd' => 'required|integer|min:1|max:999',
            'bulkCapacity' => 'required|integer|min:1|max:50',
        ];
    }

    protected $messages = [
        'number.required' => 'O numero da mesa e obrigatorio.',
        'bulkStart.required' => 'Informe o numero inicial.',
        'bulkEnd.required' => 'Informe o numero final.',
        'bulkEnd.min' => 'O numero final deve ser maior que 0.',
        'bulkCapacity.required' => 'A capacidade e obrigatoria.',
    ];

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->formMode = 'single';
        $this->showForm = true;
    }

    public function openBulkForm(): void
    {
        $this->resetForm();
        $this->bulkPrefix = 'Mesa ';
        $this->bulkStart = 1;
        $this->bulkEnd = 10;
        $this->bulkCapacity = 4;
        $this->formMode = 'bulk';
        $this->showForm = true;
    }

    public function resetForm(): void
    {
        $this->resetValidation();
        $this->reset([
            'showForm', 'number', 'capacity', 'status', 'observation',
            'editingTableId', 'bulkPrefix', 'bulkStart', 'bulkEnd', 'bulkCapacity',
        ]);
    }

    public function edit(int $id): void
    {
        $table = Table::findOrFail($id);
        $this->editingTableId = $table->id;
        $this->number = $table->number;
        $this->capacity = $table->capacity;
        $this->status = $table->status;
        $this->observation = $table->observation ?? '';
        $this->formMode = 'single';
        $this->showForm = true;
    }

    public function save(): void
    {
        if ($this->formMode === 'bulk') {
            $this->saveBulk();
            return;
        }

        $this->validate();

        $tenant = auth()->user()->tenant;

        if (!$this->editingTableId && !$tenant->canAddTable()) {
            $this->addError('number', 'Seu plano gratuito permite apenas ' . $tenant->maxTablesAllowed() . ' mesas. Faca upgrade para Premium.');
            return;
        }

        $existing = Table::where('number', $this->number)
            ->where('id', '!=', $this->editingTableId)
            ->first();

        if ($existing) {
            $this->addError('number', 'Ja existe uma mesa com este numero.');
            return;
        }

        if ($this->editingTableId) {
            $table = Table::findOrFail($this->editingTableId);
            $table->update([
                'number' => $this->number,
                'capacity' => $this->capacity,
                'status' => $this->status,
                'observation' => $this->observation ?: null,
            ]);
            $this->dispatch('notify', message: 'Mesa ' . $this->number . ' atualizada!');
        } else {
            Table::create([
                'tenant_id' => $tenant->id,
                'number' => $this->number,
                'capacity' => $this->capacity,
                'status' => $this->status,
                'observation' => $this->observation ?: null,
            ]);
            $this->dispatch('notify', message: 'Mesa ' . $this->number . ' criada!');
        }

        $this->resetForm();
    }

    public function saveBulk(): void
    {
        $this->validate();

        $tenant = auth()->user()->tenant;

        if ($this->bulkStart > $this->bulkEnd) {
            [$this->bulkStart, $this->bulkEnd] = [$this->bulkEnd, $this->bulkStart];
        }

        $qty = $this->bulkEnd - $this->bulkStart + 1;

        if (!$tenant->canAddTable() && $tenant->tables()->count() + $qty > $tenant->maxTablesAllowed()) {
            $this->addError('bulkEnd', 'Limite de ' . $tenant->maxTablesAllowed() . ' mesas excedido. Faca upgrade para Premium.');
            return;
        }

        $created = 0;
        $existing = Table::whereIn('number', range($this->bulkStart, $this->bulkEnd))
            ->pluck('number')
            ->toArray();

        for ($i = $this->bulkStart; $i <= $this->bulkEnd; $i++) {
            $number = $this->bulkPrefix ? $this->bulkPrefix . $i : (string) $i;

            if (in_array($number, $existing)) {
                continue;
            }

            if (!$tenant->canAddTable()) {
                break;
            }

            Table::create([
                'tenant_id' => $tenant->id,
                'number' => $number,
                'capacity' => $this->bulkCapacity,
                'status' => 'free',
            ]);
            $created++;
        }

        $this->showForm = false;

        if ($created > 0) {
            $this->dispatch('notify', message: $created . ' mesas criadas com sucesso!');
        } else {
            $this->dispatch('notify', message: 'Nenhuma mesa foi criada (todas ja existem ou limite atingido).');
        }
    }

    public function delete(int $id): void
    {
        $table = Table::findOrFail($id);
        $number = $table->number;

        if ($table->orders()->whereIn('status', ['novo', 'em_preparo', 'saiu_entrega'])->exists()) {
            $this->dispatch('notify', message: 'Nao e possivel excluir a mesa ' . $number . ' com pedidos em andamento.');
            return;
        }

        $table->delete();
        $this->dispatch('notify', message: 'Mesa ' . $number . ' excluida!');
    }

    public function toggleStatus(int $id): void
    {
        $table = Table::findOrFail($id);
        $newStatus = match ($table->status) {
            'free' => 'occupied',
            'occupied' => 'reserved',
            'reserved' => 'free',
            default => 'free',
        };
        $table->update(['status' => $newStatus]);
    }

    public function showQrCode(int $id): void
    {
        $table = Table::with('tenant')->findOrFail($id);
        $this->qrTableId = $id;
        $this->qrTableNumber = $table->number;

        $this->qrUrl = route('menu.show', [
            'slug' => $table->tenant->slug,
            'token' => $table->token,
        ]);

        $result = new Builder(
            writer: new PngWriter,
            data: $this->qrUrl,
            encoding: new Encoding('UTF-8'),
            size: 300,
            margin: 10,
        );

        $this->qrImage = 'data:image/png;base64,' . base64_encode($result->build()->getString());

        $this->showQr = true;
    }

    public function closeQrCode(): void
    {
        $this->showQr = false;
        $this->qrTableId = null;
        $this->qrTableNumber = null;
        $this->qrUrl = '';
        $this->qrImage = '';
    }

    #[Computed]
    public function tables()
    {
        $tenant = auth()->user()->tenant;
        $query = $tenant->manageableTables()->with('tenant');

        if ($this->search) {
            $query->where('number', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query->paginate(12);
    }

    #[Computed]
    public function stats()
    {
        $tenant = auth()->user()->tenant;
        $q = $tenant->manageableTables();

        $total = (clone $q)->count();
        $free = (clone $q)->where('status', 'free')->count();
        $occupied = (clone $q)->where('status', 'occupied')->count();
        $reserved = (clone $q)->where('status', 'reserved')->count();

        return compact('total', 'free', 'occupied', 'reserved');
    }

    public function render()
    {
        return view('livewire.admin.tables-page', [
            'stats' => $this->stats,
        ])->layout('layouts.admin');
    }
}
