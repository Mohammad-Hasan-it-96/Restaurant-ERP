<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    /**
     * Mass-assignable attributes.
     *
     * `role` is deliberately EXCLUDED: it is the privilege boundary, so it must
     * never be settable from a request payload via fill()/create()/update().
     * Every legitimate write assigns it explicitly (UserController store/update,
     * InstallController) so a future `User::create($request->all())` can't
     * escalate a user to admin.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_picture',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user has a specific role
     *
     * @param  string  $role
     * @return bool
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    /**
     * Get the profile picture URL
     *
     * @return string
     */
    public function getProfilePictureUrl()
    {
        if ($this->profile_picture && file_exists(public_path('storage/'.$this->profile_picture))) {
            return asset('storage/'.$this->profile_picture);
        }

        return asset('images/default-avatar.png');
    }

    /**
     * Get the products for the user.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
