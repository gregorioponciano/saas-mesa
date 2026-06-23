<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketMessage extends Model
{
    protected $fillable = [
        'ticket_id', 'user_id', 'body',
        'is_internal', 'author_role', 'author_name',
    ];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }

    public function ticket() { return $this->belongsTo(SupportTicket::class); }
    public function user()   { return $this->belongsTo(User::class); }
}
