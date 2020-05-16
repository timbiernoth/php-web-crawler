<?php

class ParseUrls
{
    public $api = [];

    protected $urls = [];

    private $config = [
        'path' => [
            'split' => '/',
        ],
    ];

    public function __construct($config = [])
    {
        $this->setConfig($config);
    }

    public function setUrls($urls)
    {
        $this->urls = $urls;
        $this->setApi();
    }

    private function setApi()
    {
        foreach ($this->urls as $url) {

            $parse = $this->getParse($url);

            $prot = $parse['prot'];
            $domain = $parse['domain'];
            $port = $parse['port'];
            $path = $parse['path'];
            $param = $parse['param'];
            $hash = $parse['hash'];
            $user = $parse['user'];
            $pass = $parse['pass'];

            $domain_names = $this->getDomainNames($domain);

            $this->api[$url] = [
                'domain' => $domain,
                'is_ip' => $domain_names['is_ip'],
                'array' => [
                    'prot' => [$prot],
                    'user' => $this->getUser($user),
                    'pass' => $this->getPass($pass),
                    'name' => $domain_names['array']['name'],
                    'tld' => $domain_names['array']['tld'],
                    'port' => $this->getPort($port),
                    'path' => $this->getPathes($path),
                    'param' => $this->getParams($param),
                    'hash' => $this->getHashes($hash),
                ],
                'string' => [
                    'prot' => $prot . '://',
                    'user' => ($user ? '' : '') . (!$user ? '' : $user . ':'),
                    'pass' => ($pass ? '' : '') . (!$pass ? '' : $pass . '@'),
                    'name' => $domain_names['string']['name'],
                    'tld' => $domain_names['string']['tld'],
                    'port' => ($port ? '' : '') . (!$port ? '' : ':' . $port),
                    'path' => $path,
                    'param' => ($param ? '' : '') . (!$param ? '' : '?' . $param),
                    'hash' => ($hash ? '' : '') . (!$hash ? '' : '#' . $hash),
                ],
            ];
        }
    }

    private function getDomainNames($domain)
    {
        $is_ip = false;

        $data = [
            'array' => [
                'name' => [],
                'tld' => [],
            ],
            'string' => [
                'name' => '',
                'tld' => '',
            ],
        ];

        if ($this->isIp($domain) !== false) {
            $is_ip = true;
            $data = $this->getIp($domain);
        } else {

            if (substr_count($domain, '.') == 0) {
                $data = $this->getDot0($domain);
            } else {
                $data = $this->getDefault($domain);
            }

        }

        return [
            'is_ip' => $is_ip,
            'array' => [
                'name' => $data['array']['name'],
                'tld' => $data['array']['tld'],
            ],
            'string' => [
                'name' => $data['string']['name'],
                'tld' => $data['string']['tld'],
            ],
        ];
    }

    private function getDefault($input)
    {
        $temp = explode('.', $input);
        $tld = end($temp);
        $name = substr($input, 0, -(strlen($tld)+1));
        $names = [];

        if (substr_count($name, '.') >= 1) {
            foreach (explode('.', $name) as $n) {
                array_push($names, $this->getNameKeys($n));
            }
        } else {
            $names = $this->getNameKeys($name);
        }

        return [
            'array' => [
                'name' => $names,
                'tld' => [$tld],
            ],
            'string' => [
                'name' => $name,
                'tld' => $tld,
            ],
        ];
    }

    private function getDot0($input)
    {
        $name = $input;
        $tld = '';

        $names = [];
        $tlds = [];

        if (substr_count($input, '-') >= 1) {
            foreach (explode('-', $input) as $n) {
                array_push($names, $n);
            }
        } else {
            $names = [$input];
        }

        return [
            'array' => [
                'name' => $names,
                'tld' => $tlds,
            ],
            'string' => [
                'name' => $name,
                'tld' => $tld,
            ],
        ];
    }

    private function getNameKeys($input)
    {
        $output = [];

        if (substr_count($input, '-') >= 1) {
            foreach (explode('-', $input) as $n) {
                array_push($output, $n);
            }
        } else {
            $output = [$input];
        }

        return $output;
    }

    private function getPathes($input)
    {
        $output = [];
        $c = 0;
        foreach (explode($this->config['path']['split'], $input) as $value) {
            if ($c >= 1) {
                array_push($output, $this->config['path']['split'] . $value);
            }
            $c++;
        }
        return $output;
    }

    private function getParams($input)
    {
        $output = [];
        foreach (explode('&', $input) as $value) {
            if ($value !== '') {
                #array_push($output, $value);
                if (substr_count($value, '=') >= 1) {
                    $key = '';
                    $val = '';
                    $c = 0;
                    foreach (explode('=', $value) as $keyval) {
                        if ($c % 2 != 0) {
                            $val = $keyval;
                        } else {
                            $key = $keyval;
                        }
                        $c++;
                    }
                    $output[$key] = $val;
                } else {
                    $output[$value] = '';
                }
            }
        }
        return $output;
    }

    private function getHashes($input)
    {
        $output = [];
        foreach (explode('#', $input) as $value) {
            if ($value !== '') {
                array_push($output, '#' . $value);
            }
        }
        return $output;
    }

    private function getPort($input)
    {
        $output = [];

        if ($input !== '') {
            $output = [$input];
        }

        return $output;
    }

    private function getUser($input)
    {
        $output = [];

        if ($input !== '') {
            $output = [$input];
        }

        return $output;
    }

    private function getPass($input)
    {
        $output = [];

        if ($input !== '') {
            $output = [$input];
        }

        return $output;
    }

    private function getParse($url)
    {
        $parse = parse_url($url);

        $prot = '';
        if (isset($parse['scheme']) && ! empty($parse['scheme'])) {
            $prot = $parse['scheme'];
        }

        $domain = '';
        if (isset($parse['host']) && ! empty($parse['host'])) {
            $domain = $parse['host'];
        }

        $port = '';
        if (isset($parse['port']) && ! empty($parse['port'])) {
            $port = $parse['port'];
        }

        $path = '';
        if (isset($parse['path']) && ! empty($parse['path'])) {
            $path = $parse['path'];
        }

        $param = '';
        if (isset($parse['query']) && ! empty($parse['query'])) {
            $param = $parse['query'];
        }

        $hash = '';
        if (isset($parse['fragment']) && ! empty($parse['fragment'])) {
            $hash = $parse['fragment'];
        }

        $user = '';
        if (isset($parse['user']) && ! empty($parse['user'])) {
            $user = $parse['user'];
        }

        $pass = '';
        if (isset($parse['pass']) && ! empty($parse['pass'])) {
            $pass = $parse['pass'];
        }

        return [
            'prot' => $prot,
            'domain' => $domain,
            'port' => $port,
            'path' => $path,
            'param' => $param,
            'hash' => $hash,
            'user' => $user,
            'pass' => $pass,
        ];
    }

    private function getIp($input)
    {
        $arr = [];

        foreach (explode('.', $input) as $value) {
            if ($value !== '') {
                array_push($arr, $value);
            }
        }

        return [
            'array' => [
                'sub' => [],
                'name' => $arr,
                'tld' => [],
            ],
            'string' => [
                'sub' => '',
                'name' => $input,
                'tld' => '',
            ],
        ];
    }

    private function isIp($domain)
    {
        $domain_string = str_replace(['.', ':'], '', $domain);

        if (is_numeric($domain_string)) {
            return true;
        }

        return false;
    }

    private function setConfig($config)
    {
        if (isset($config['path']['split']) && ! empty($config['path']['split'])) {
            $this->config['path']['split'] = $config['path']['split'];
        }
    }
}
