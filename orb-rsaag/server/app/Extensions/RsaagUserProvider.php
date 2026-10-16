<?php

namespace App\Extensions;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class RsaagUserProvider extends EloquentUserProvider
{

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) || (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return;
        }

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        $query = $this->createModel()->newQuery();

        foreach ($credentials as $key => $value) {
            if (Str::contains($key, 'password')) {
                continue;
            }
            if (Str::contains($key, 'login')) {
                $value = strtolower($value);
            }

            if (is_array($value) || $value instanceof Arrayable) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        $query->where('email_verified_at', '<>', null);
        $query->where('blocked', '<>', true);
        $query->where('is_deleted', '<>', true);

        return $query->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        $plain = $credentials['password'];

        $check = $this->hasher->check($plain, $user->getAuthPassword());

        if (!$check && $user->getAttribute('is_old')) {
            if (md5($plain) == $user->getAttribute('password')) {
                $user->setAttribute('password', $this->hasher->make($plain));
                $user->setAttribute('is_old', false);

                $user->save();

                $check = $this->hasher->check($plain, $user->getAuthPassword());
            }
        }

        return $check;
    }
}
