<?php

namespace App\Helpers;

use Hashids\Hashids;

class HashHelper {

  protected static $min_length = 5;

  public static function generateSalt($salt){
    return config('app.salt') . '-' . $salt;
  }

  public static function HashId($salt, $value){
    $hashid = new Hashids(self::generateSalt($salt), self::$min_length);
    return $hashid->encode($value);
  }

  public static function DecodeId($salt, $value){
    $hashid = new Hashids(self::generateSalt($salt), 5);
    $decoded = $hashid->decode($value);
    return count($decoded) ? $decoded[0] : null;
  }

}
