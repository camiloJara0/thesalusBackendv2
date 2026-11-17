<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'correo',
        'accion',
        'modelo',
        'modelo_id',
        'descripcion',
        'ip',
        'user_agent',
        'datos',
        'created_at',
    ];

    protected $casts = [
        'datos' => 'array',
        'created_at' => 'datetime',
    ];
}
