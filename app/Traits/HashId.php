<?php

namespace App\Traits;

use App\Helpers\HashHelper;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;

trait HashId{

  public function getHidAttribute(){
    $salt = strtolower(class_basename($this));
    return HashHelper::HashId($salt, $this->id);
  }

  public function scopeByHash(Builder $query, string $hash): Builder
  {
    $salt = strtolower(class_basename($this));
    return  $query->where($this->getKeyName(), HashHelper::DecodeId($salt, $hash));
  }

  public static function byHash($id)
  {
    return self::query()->byHash($id)->first();
  }

  public function resolveRouteBinding($value, $field = null)
  {
    $salt = strtolower(class_basename($this));
    $id = HashHelper::DecodeId($salt, $value);
    return parent::where($this->getKeyName(), $id)->first();
  }

  public function getRouteKey(){
    return $this->hid;
  }

}
