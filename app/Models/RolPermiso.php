<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Clase asociativa Rol 0..* -- 1..* Permiso
class RolPermiso extends Model
{
    protected $table = 'rol_permiso';

    protected $fillable = [
        'rol_id',
        'permiso_id',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function permiso()
    {
        return $this->belongsTo(Permiso::class, 'permiso_id');
    }
}
