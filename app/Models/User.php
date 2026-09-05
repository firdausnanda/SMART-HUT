<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Lab404\Impersonate\Models\Impersonate;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{

    use HasFactory, Notifiable, HasRoles, Impersonate, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'google_id',
        'avatar',
        'cdk_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getRoleDescriptionAttribute()
    {
        return $this->roles->pluck('description')->first();
    }

    public function cdk()
    {
        return $this->belongsTo(Cdk::class);
    }

    public function isAdminProvinsi(): bool
    {
        return is_null($this->cdk_id) && $this->hasRole(['admin', 'admin_provinsi']);
    }

    public function getRoleLevel(): int
    {
        if ($this->hasRole('admin')) return 6;
        if ($this->hasRole('admin_provinsi')) return 5;
        if ($this->hasRole('admin_cdk')) return 4;
        if ($this->hasRole('kacdk')) return 3;
        if ($this->hasRole('kasi')) return 2;
        
        return 1;
    }
}
