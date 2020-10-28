<?php

namespace Module;

use Http\Redirect;

class Auth extends \Module
{
    public function index()
    {
        return \View::make('login');
    }

    public function login()
    {
        $_SESSION['token'] = $this->request->post('token');

        $this->middleware(\Middleware\Auth::class);

        $user_id = $_SESSION['user']['id'];

        $profile = $this->db->query("select * from profile where uid = ?", $user_id, true);var_dump($profile);

        if (is_null($profile))
            $this->db->exec("insert into profile (uid, gacha_times) values (?, ?)", [$user_id, 1]);

        return Redirect::to('/');
    }

    public function logout()
    {
        unset($_SESSION['token']);
        unset($_SESSION['user']);

        return Redirect::to('/auth/index');
    }
}