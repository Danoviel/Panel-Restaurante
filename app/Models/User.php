<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    
    use HasFactory, Notifiable;

    
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'password',
        'rol_id',
        'activo'
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean'
        ];
    }

    /// JWT Metodos

    //Devuelve el identificador que se almacenará en el sujeto del JWT.
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    //Devuelve una matriz de valores clave que contiene cualquier reclamo personalizado que se agregará al JWT.
    public function getJWTCustomClaims()
    {
        return [
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'email' => $this->email,
        ];
    }

    /// Relacion

    // Un usuario pertenece a un rol
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    // Un usuario (mesero) tiene muchas ordenes
    public function ordenes()
    {
        return $this->hasMany(Orden::class, 'usuario_id');
    }

    // Un usuario (cajero) tiene muchas cajas
    public function cajas()
    {
        return $this->hasMany(Caja::class, 'usuario_id');
    }
}
