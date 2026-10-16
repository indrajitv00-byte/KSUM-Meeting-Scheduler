<?php

namespace App\Traits;

trait AutoFill {

  public function autofill($data){
    foreach($data as $key => $value){
      $this->{$key} = $value;
    }
  }

}
