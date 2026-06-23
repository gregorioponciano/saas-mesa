<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([TenantScope::class])]
class SupportTicket extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'assigned_to', 'subject',
        'category', 'priority', 'status', 'order_id',
    ];

    public const CATEGORY_LABELS = [
        'pedido'    => 'Problema com Pedido',
        'pagamento' => 'Pagamento',
        'cardapio'  => 'Cardápio',
        'entrega'   => 'Entrega',
        'conta'     => 'Minha Conta',
        'outro'     => 'Outro',
    ];

    public const PRIORITY_LABELS  = ['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta'];
    public const PRIORITY_CLASSES  = [
        'baixa' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
        'media' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
        'alta'  => 'bg-red-500/10 text-red-400 border border-red-500/20',
    ];

    public const STATUS_LABELS  = [
        'aberto'              => 'Aberto',
        'em_atendimento'      => 'Em Atendimento',
        'aguardando_cliente'  => 'Aguardando Cliente',
        'resolvido'           => 'Resolvido',
        'fechado'             => 'Fechado',
    ];

    public const STATUS_CLASSES  = [
        'aberto'             => 'bg-red-500/10 text-red-400 border border-red-500/20',
        'em_atendimento'     => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
        'aguardando_cliente' => 'bg-sky-500/10 text-sky-400 border border-sky-500/20',
        'resolvido'          => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
        'fechado'            => 'bg-neutral-500/10 text-neutral-400 border border-neutral-500/20',
    ];

    public const STATUS_OPEN   = ['aberto', 'em_atendimento', 'aguardando_cliente'];
    public const STATUS_CLOSED = ['resolvido', 'fechado'];

    public function tenant()      { return $this->belongsTo(Tenant::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function assignedTo()  { return $this->belongsTo(User::class, 'assigned_to'); }
    public function messages()    { return $this->hasMany(SupportTicketMessage::class, 'ticket_id'); }
    public function lastMessage() { return $this->hasOne(SupportTicketMessage::class, 'ticket_id')->latestOfMany(); }

    public function categoryLabel(): string { return self::CATEGORY_LABELS[$this->category] ?? $this->category; }
    public function priorityLabel(): string { return self::PRIORITY_LABELS[$this->priority] ?? $this->priority; }
    public function priorityClasses(): string { return self::PRIORITY_CLASSES[$this->priority] ?? ''; }
    public function statusLabel(): string   { return self::STATUS_LABELS[$this->status] ?? $this->status; }
    public function statusClasses(): string { return self::STATUS_CLASSES[$this->status] ?? ''; }
    public function isOpen(): bool          { return in_array($this->status, self::STATUS_OPEN); }
    public function isClosed(): bool        { return in_array($this->status, self::STATUS_CLOSED); }
}
