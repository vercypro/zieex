<?php
declare(strict_types=1);

namespace App\Models;

use Zieex\Database\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected array $hidden        = ['password'];
    protected array $casts         = [
        'id' => 'string',
    ];
}
