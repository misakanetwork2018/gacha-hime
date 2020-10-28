<?php

class Api
{
    /**
     * @var string
     */
    private $api;

    public function __construct()
    {
        $this->api = App::config('api');
    }

    public function checkOK($data)
    {
        return empty($data) || isset($data['error']) ? false : true;
    }

    public function getUserInfo($token)
    {
        $data = curl_get($this->api . '/user/info/base', ['Authorization: Bearer ' . $token]);

        if (!$this->checkOK($data)) return false;

        return $data;
    }

    public function getServiceBaseType($token, $pid)
    {
        $data = curl_get("{$this->api}/product/pluginGet?id=$pid&key=fields",
            ['Authorization: Bearer ' . $token]);

        if (!$this->checkOK($data)) return false;

        return $data;
    }
}