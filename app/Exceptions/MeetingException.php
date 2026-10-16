<?php

namespace App\Exceptions;

use RuntimeException;

/** A problem we can show to the person as-is (permissions, Zoom errors, bad state). */
class MeetingException extends RuntimeException
{
}
