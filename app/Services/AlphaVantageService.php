<?php

namespace App\Services;

class AlphaVantageService
{
    public string $function;

    public array $param;
    private string   $key;
    private string   $rootURL = 'https://www.alphavantage.co/query';

    public function __construct(string $function)
    {
        $this->key = env('API_KEY_ALPHALVATANGE');
        $this->function = $function;
    }

    public function getFullURL($param): string
    {
        $paramToString = '';
        if (!empty($param)) {
            foreach($param as $key => $value) {
                $paramToString .= "&$key=$value";
            }
        }
        return  "$this->rootURL?function=$this->function&apikey=$this->key" . $paramToString;
    }
}
