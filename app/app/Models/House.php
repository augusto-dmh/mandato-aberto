<?php

namespace App\Models;

/** A legislative house; Presidência is not one and gets its own entity in its own feature. */
enum House: string
{
    case Camara = 'camara';
    case Senado = 'senado';
}
