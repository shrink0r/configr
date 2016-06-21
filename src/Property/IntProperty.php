<?php

namespace Shrink0r\Configr\Property;

use Shrink0r\Configr\Error;
use Shrink0r\Configr\Ok;

class IntProperty extends Property
{
    protected function validateValue($value)
    {
        return is_int($value) ? Ok::unit() : Error::unit([ Error::NON_INT ]);
    }
}