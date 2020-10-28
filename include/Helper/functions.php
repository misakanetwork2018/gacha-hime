<?php

if (!function_exists('curl_get')) {
    /**
     * 仅适用于json传输
     *
     * @param $url
     * @param array $headers
     * @return mixed
     */
    function curl_get($url, $headers = [])
    {
        $headerArray = array_merge(["Content-type:application/json;", "Accept:application/json"], $headers);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
        return $output;
    }
}

if (!function_exists('curl_post')) {
    /**
     * 仅适用于json传输
     *
     * @param $url
     * @param $data
     * @param array $headers
     * @return mixed
     */
    function curl_post($url, $data, $headers = [])
    {
        $data = json_encode($data);
        $headerArray = array_merge(["Content-type:application/json;charset=utf-8", "Accept:application/json"], $headers);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output, true);
    }
}