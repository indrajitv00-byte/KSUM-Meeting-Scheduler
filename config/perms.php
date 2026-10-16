<?php

$abilities = ['view', 'create', 'update', 'delete'];

$roles = [
  // "common" permissions are given to every role (user, admin, super).
  'common' => [
    'home' => ['view'],
    'meetings' => ['view', 'create', 'cancel'],
  ],
  'admin' => [
    'dashboard' => ['view'],
    'users' => $abilities,
    'roles' => $abilities,
    'zoom-accounts' => ['view', 'create', 'update'],
    // view = see every meeting, update = start / cancel / download recordings of other people's meetings
    'meetings' => ['view', 'update'],
  ],

];

$permissions = [];

foreach($roles as $role => $types){
  foreach($types as $type => $ability){
    foreach($ability as $item){

        $perms = $role=='common'? $type.':'.$item : $role.':'.$type.':'.$item;

        array_push($permissions, $perms);

    }
  }
}

return $permissions;
