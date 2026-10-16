<?php

function hideEmail($email, $length = 3, $domain = 1)
{
    if(filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        list($first, $last) = explode('@', $email);
        $first = str_replace(substr($first, $length), str_repeat('*', strlen($first)-$length), $first);
        $last = explode('.', $last);
        $last_domain = str_replace(substr($last['0'], $domain), str_repeat('*', strlen($last['0'])-$domain), $last['0']);
        $hideEmailAddress = $first.'@'.$last_domain.'.'.$last['1'];
        return $hideEmailAddress;
    }
}

function hidePhone($phone, $length=2){

  return str_replace(substr($phone, $length, strlen($phone)-($length * 2) ), str_repeat('*', strlen($phone)-$length), $phone);

}
