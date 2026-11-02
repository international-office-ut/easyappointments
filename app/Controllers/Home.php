<?php

namespace App\Controllers;

use Exception;
class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }
}
