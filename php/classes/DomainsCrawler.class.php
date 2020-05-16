<?php

class DomainsCrawler
{
    public $api = [];

    private $curl = [];
    private $domains = [];
    private $loadtime = [];
    private $db = [];

    private $crawler = [
        'todo' => [],
        'done' => [],
    ];

    private $config = [
        'max_domains' => 10,
        'max_loops' => 10,
    ];

    public function __construct($config = [], $curl = [], $domains = [], $loadtime = [], $db = [])
    {
        $this->setLoadtime($loadtime);
        $this->setConfig($config);
        $this->setCurl($curl);
        $this->setDomains($domains);
        $this->setDb($db);

        $this->start = time();

        $this->go(0);
    }

    private function go($i)
    {
        $this->loadtime->start();

        $this->api[$i] = [
            'crawler' => [
                'max_domains' => $this->config['max_domains'],
                'datetime' => get_datetime(),
                'loadtime' => 0,
            ],
        ];

        $c = 0;
        foreach ($this->domains->todo as $domain_data) {

            if (count($this->crawler['todo']) < $this->config['max_domains']) {
                array_push($this->crawler['todo'], CONFIG_BOT_GET_PAGES_URL . $domain_data['url']);
            }

            $c++;
        }

        $c = 0;
        foreach ($this->curl->getContents($this->crawler['todo']) as $json) {

            $this->api[$i]['domains'][$this->domains->todo[$c]['url']] = [
                'db' => $this->domains->todo[$c]['db'],
                'data' => json_decode($json),
            ];

            array_push($this->crawler['done'], $this->domains->todo[$c]['url']);
            unset($this->crawler['todo'][$c]);

            $c++;
        }

        $this->setLoadtimeData($i);

        $this->crawler['todo'] = [];
        $this->domains->setNextDomains($this->config['max_domains']);

        #pre($this->domains->todo);

        if ($i < $this->config['max_loops'] &&
            ! empty($this->domains->todo)) {
            $this->go(($i+1));
        }
    }

    private function setLoadtimeData($i)
    {
        $this->loadtime->stop();
        $this->api[$i]['crawler']['loadtime'] = $this->loadtime->output;
    }

    private function setCurl($curl)
    {
        if ( ! empty($curl)) {
            $this->curl = $curl;
        } else {
            prexit('No Curl!');
        }
    }

    private function setDomains($domains)
    {
        if ( ! empty($domains)) {
            $this->domains = $domains;
        } else {
            prexit('No Domains!');
        }
    }

    private function setLoadtime($loadtime)
    {
        if ( ! empty($loadtime)) {
            $this->loadtime = $loadtime;
        } else {
            prexit('No Loadtime!');
        }
    }

    private function setDb($db)
    {
        if ( ! empty($db)) {
            $this->db = $db;
        } else {
            prexit('No Database!');
        }
    }

    private function setConfig($config)
    {
        if ( isset($config['max_domains']) ) {
            $this->config['max_domains'] = $config['max_domains'];
        }

        if ( isset($config['max_loops']) ) {
            $this->config['max_loops'] = $config['max_loops'];
        }
    }
}
