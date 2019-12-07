<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;


class Vote extends Model implements JWTSubject, AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $guarded = [];


      public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }

    public function user()
    {
        return $this->hasMany('App\User', 'users', 'owner_id', 'id');
    }

    public function voter_users()
    {
        return $this->hasOne('App\User', 'id', 'voter_id');
    }

    public function option()
    {
        return $this->belongsTo('App\Option');
    }

    public function poll()
    {
        return $this->belongsTo('App\Option');
    }
}
