<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;  // All authenticated users can view the list
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        return true;  // All authenticated users can view product details
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin' || $user->role === 'moderator';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        // Admin can update any product
        if ($user->role === 'admin') {
            return true;
        }

        // Moderator can only update their own products
        if ($user->role === 'moderator') {
            return $user->id === $product->user_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        // Admin can delete any product
        if ($user->role === 'admin') {
            return true;
        }

        // Moderator can only delete their own products
        if ($user->role === 'moderator') {
            return $user->id === $product->user_id;
        }

        return false;
    }
}
