<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Profile extends BaseController
{
    public function index()
    {
        $data = [
            'username'   => session()->get('username') ?? '-',
            'role'       => session()->get('role') ?? '-',
            'email'      => session()->get('email') ?? '-',
            'login_time' => session()->get('login_time') ?? '-',
        ];

        return view('profile', $data);
    }
}