<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use Illuminate\Contracts\Auth\Access\Gate;


abstract class Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
}
