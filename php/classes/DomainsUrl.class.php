<?php

class DomainsUrl
{
    private $db = [];

    public $todo = [];

    private $isBlacklisted = [
        'tlds_id' => [
            1 => true,
            2 => true,
            1298 => true,
            1299 => true,
            1300 => true,
            1301 => true,
            1302 => true,
            1303 => true,
        ],
        'names_id' => [
            1 => true,
            2 => true,
            3 => true,
            4 => true,
            5 => true,
            6 => true,
        ],
    ];

    public function __construct($db = [])
    {
        $this->setDb($db);
        $this->setTodo();
    }

    private function setTodo()
    {
        $this->setDbDomains(CONFIG_BOT_GET_START_URLS);
    }

    public function setNextDomains($max_domains)
    {
        if (LOCAL !== false) {
            unset($this->isBlacklisted['tlds_id'][1300]);
        }

        $this->todo = [];

        $sql = "SELECT *
        FROM 001_010_domains";
        $domains = $this->db->get($sql, ['id', 'names_id', 'tlds_id']);

        $domains_urls = [];
        foreach ($domains as $key => $data) {

            $sql = "SELECT slug
            FROM 000_040_names
            WHERE id = " . $data['names_id'];
            $name_data = $this->db->get($sql, ['slug']);
            $name = $name_data[1]['slug'];

            $sql = "SELECT slug
            FROM 000_050_tlds
            WHERE id = " . $data['tlds_id'];
            $tld_data = $this->db->get($sql, ['slug']);
            $tld = $tld_data[1]['slug'];

            $domain_url = 'https://' . $name . substr($tld, 1) . '/';
            $domains_urls[$domain_url] = [
                'domains_id' => $data['id'],
                'names_id' => $data['names_id'],
                'tlds_id' => $data['tlds_id'],
            ];
        }

        $sql = "SELECT *
        FROM 001_020_domains_crawler";
        $domains_crawler = $this->db->get($sql, ['domains_id', 'domains_url']);
        $domains_done = [];
        foreach ($domains_crawler as $key => $data) {
            $domains_done[$data['domains_url']] = [
                'domains_id' => $data['domains_id'],
            ];
        }

        $c = 0;
        foreach ($domains_urls as $url => $data) {
            if ( ! isset($domains_done[$url]) &&
                 ! isset($this->isBlacklisted['names_id'][$data['names_id']]) &&
                 ! isset($this->isBlacklisted['tlds_id'][$data['tlds_id']])) {
                $this->todo[$c] = [
                    'url' => $url,
                    'db' => [
                        'prots_id' => 3,
                        'names_id' => $data['names_id'],
                        'tlds_id' => $data['tlds_id'],
                        'domains_id' => $data['domains_id'],
                    ]
                ];
                $c++;
            }
        }

        #pre($this->todo);

    }

    private function setDbDomains($domains)
    {
        foreach ($domains as $domain) {

            $ids = $this->db->dataByDomain($domain);

            array_push($this->todo, [
                'url' => $domain,
                'db' => $ids,
            ]);

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
}
