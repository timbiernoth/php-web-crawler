<?php

class DataBaseCrawler
{
    public $defaults = [];
    public $blacklistDomains = [];

    private $db = [];

    private $config = [
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => '',
        'database' => '',
    ];

    private $table = [];

    private $currentFunction = '';

    public function __construct($config = [], $tables = [])
    {
        $this->setConfig($config);
        $this->setTables($tables);
        $this->setDb();
        $this->setDefaults();
        $this->setBlacklistDomains();
    }

    public function dataByDomain($url)
    {
        $this->currentFunction = 'dataByDomain';

        $url_info = get_url_info($url);

        $prots_id = $this->defaults['prots'][$url_info['prot']]['id'];

        $names_id = $this->dataByValues([
            'table' => 'names',
            'datas' => [
                'slug' => $url_info['name'],
                'is_www' => $url_info['is_www'],
            ],
        ]);

        $tld = $url_info['tld'];
        if (is_ip($url_info['name']) !== false) {
            $tld = '';
        }
        $tlds_id = $this->defaults['tlds'][$tld]['id'];

        $domains_id = $this->dataByIds([
            'table' => 'domains',
            'ids' => [
                'names_id' => $names_id,
                'tlds_id' => $tlds_id,
            ],
        ]);

        $output = [
            'prots_id' => $prots_id,
            'names_id' => $names_id,
            'tlds_id' => $tlds_id,
            'domains_id' => $domains_id,
        ];

        return $output;
    }

    public function dataByValues($data, $type = '') // select | insert | update
    {
        $this->currentFunction = 'dataByValues';

        $last_crawled = [];
        if (isset($data['datas']['last_crawled'])) {
            $last_crawled = ['last_crawled' => $data['datas']['last_crawled']];
            unset($data['datas']['last_crawled']);
        }

        $id = $this->selectId(
            $this->table[$data['table']],
            $data['datas']
        );

        if ($type !== 'select') {

            $datas = array_merge($last_crawled, $data['datas']);

            if ($type == 'insert') {

                $id = $this->insert(
                    $this->table[$data['table']],
                    $datas
                );

            } else {

                if ($id == 0) {

                    $id = $this->insert(
                        $this->table[$data['table']],
                        $datas
                    );

                } else {

                    if ($type == 'update') {
                        $this->update(
                            $this->table[$data['table']],
                            $datas,
                            ['id' => $id]
                        );
                    }

                }

            }

        }

        return (int)$id;
    }

    public function dataByIds($data, $type = '') // select | insert | update
    {
        $this->currentFunction = 'dataByIds';

        $id = $this->selectId(
            $this->table[$data['table']],
            $data['ids']
        );

        if ($type !== 'select') {

            $datas = [];
            if (isset($data['datas'])) {
                $datas = $data['datas'];
            }
            $ids_and_datas = array_merge($data['ids'], $datas);

            if ($type == 'insert') {

                $id = $this->insert(
                    $this->table[$data['table']],
                    $ids_and_datas
                );

            } else {

                if ($id == 0) {

                    $id = $this->insert(
                        $this->table[$data['table']],
                        $ids_and_datas
                    );

                } else {

                    if ($type == 'update') {
                        $this->update(
                            $this->table[$data['table']],
                            $ids_and_datas,
                            ['id' => $id]
                        );
                    }

                }

            }

        }

        return (int)$id;
    }

    public function isNotBlacklistedDomain($url)
    {
        $this->currentFunction = 'isNotBlacklistedDomain';

        $host = '';
        $temp = parse_url($url);
        if (isset($temp['path']) && ! isset($temp['host'])) {
            $host = $temp['path'];
        }
        if (isset($temp['host'])) {
            $host = $temp['host'];
        }

        $temp = explode('.', $host);
        $tld = '.' . end($temp);
        $name = substr($host, 0, -(strlen($tld)-1));
        if (is_ip($url) !== false) {
            $name = $host;
            $tld = '';
        }

        foreach ($this->blacklistDomains as $key => $data) {
            $tld_db_arr = $this->select(['slug'], $this->table['tlds'], ['id' => $data['tlds_id']]);
            $tld_db = $tld_db_arr[1]['slug'];
            $tld_short = substr($tld, 1);

            $sum = [];
            $name_rev = split_domain($name);
            $c = 0;
            foreach (explode('.', strrev($data['name'])) as $n) {
                if (isset($name_rev[$c]) && $n == $name_rev[$c] && $n !== '*') {
                    array_push($sum, true);
                }
                if (isset($name_rev[$c]) && $n !== $name_rev[$c] && $n !== '*') {
                    array_push($sum, false);
                }
                $c++;
            }

            if (!in_array(false, $sum)) {
                if ($tld_db == $tld) {
                    return false;
                }
            }
        }

        return true;
    }

    private function setBlacklistDomains()
    {
        $this->currentFunction = 'setBlacklistDomains';

        $this->blacklistDomains = $this->get(
            "SELECT name, tlds_id
            FROM " . $this->table['domains_blacklist'],
            ['name', 'tlds_id']
        );
    }

    public function insertDefaults($table, $data)
    {
        $this->insert(
            $table,
            $data
        );
    }

    private function setDefaults()
    {
        $this->currentFunction = 'setDefaults';

        $langs = $this->get(
            "SELECT id, code, lang
            FROM " . $this->table['langs'],
            ['id', 'code']
        );
        foreach ($langs as $data) {
            $this->defaults['langs'][$data['code']] = [
                'id' => (int)$data['id'],
            ];
        }

        $prots = $this->get(
            "SELECT id, slug
            FROM " . $this->table['prots'],
            ['id', 'slug']
        );
        foreach ($prots as $data) {
            $this->defaults['prots'][$data['slug']] = [
                'id' => (int)$data['id'],
            ];
        }

        $tlds = $this->get(
            "SELECT id, slug
            FROM " . $this->table['tlds'],
            ['id', 'slug']
        );
        foreach ($tlds as $data) {
            $this->defaults['tlds'][$data['slug']] = [
                'id' => (int)$data['id'],
            ];
        }
    }

    private function update($table, $data = [], $where = [])
    {
        $sql = "UPDATE ".$table." SET ";

        foreach ($data as $col => $value) {
            $sql .= $col . " = '" . $this->escape($value) . "',";
        }
        $sql = substr($sql, 0, -1);

        $c = 0;
        foreach ($where as $col => $value) {
            if ($c == 0) {
                $sql .= " WHERE ".$col." = '".$this->escape($value)."'";
            } else {
                $sql .= " AND ".$col." = '".$this->escape($value)."'";
            }
            $c++;
        }
        $sql .= ";";

        if ($this->db->query($sql) !== TRUE) {
            $this->error('update', [
                'query' => $sql,
                'error' => $this->db->error,
            ]);
        }
    }

    private function insert($table, $data)
    {
        $names = [];
        $values = [];
        foreach ($data as $name => $value) {
            array_push($names, $name);
            array_push($values, $value);
        }

        $sql = "INSERT INTO " . $table . " (";

        foreach ($names as $name) {
            $sql .= $name . ",";
        }
        $sql = substr($sql, 0, -1) . ") VALUES (";

        foreach ($values as $value) {
            $sql .= "'" . $this->escape($value) . "'" . ",";
        }
        $sql = substr($sql, 0, -1) . ");";

        if ($this->db->query($sql) === TRUE) {
            return mysqli_insert_id($this->db);
        } else {
            $this->error('insert', [
                'query' => $sql,
                'error' => $this->db->error,
            ]);
        }
    }

    private function selectId($table = '', $data = [])
    {
        return $this->getId(
            $this->select(['id'], $table, $data)
        );
    }

    private function select($selects = [], $table = '', $where = [])
    {
        $sql = "SELECT ";

        foreach ($selects as $select) {
            $sql .= $this->escape($select) . ",";
        }
        $sql = substr($sql, 0, -1);

        $sql .= " FROM " . $table . " ";

        $c = 0;
        foreach ($where as $col => $value) {
            if ($c == 0) {
                $sql .= " WHERE ".$col." = '".$this->escape($value)."'";
            } else {
                $sql .= " AND ".$col." = '".$this->escape($value)."'";
            }
            $c++;
        }
        $sql .= ";";

        return $this->get($sql, $selects);
    }

    private function getId($get_id)
    {
        $id = 0;

        if ($get_id[1] !== false) {
            $id = $get_id[1]['id'];
        }

        return (int)$id;
    }

    public function get($sql, $selects = [])
    {
        $output = [];

        if ($this->db->connect_error) {
            prexit('Connection failed: ' . $this->db->connect_error);
        }

        if ($result = mysqli_query($this->db, $sql)) {

            if (mysqli_num_rows($result) > 0) {

                $c = 0;
                while ($row = mysqli_fetch_array($result)) {
                    $c++;
                    foreach ($selects as $select) {
                        $select = $this->escape($select);
                        $output[$c][$select] = $row[$select];
                    }
                }

                mysqli_free_result($result);

            } else {
                $output[1] = false;
            }

        } else {
            $output[1] = false;
            $this->error('select', [
                'query' => $sql,
                'error' => $this->db->error,
            ]);
        }

        return $output;
    }

    public function getCount($table)
    {
        $output = 0;

        if ($this->db->connect_error) {
            prexit('Connection failed: ' . $this->db->connect_error);
        }

        $sql = "SELECT COUNT(*)
        FROM " . $this->table[$table] . ";";

        $result = mysqli_query($this->db, $sql);
        $row = mysqli_fetch_array($result);
        $total = $row[0];

        return $total;
    }

    private function escape($str)
    {
        return mysqli_real_escape_string(
            $this->db,
            $str
        );
    }

    private function error($type, $data)
    {
        if (DEBUG !== false) {
            prexit(
                $type .
                "\n<br>" .
                $this->currentFunction .
                "\n<br>" .
                $data['query'] .
                "\n<br>" .
                $data['error']
            );
        }
    }

    private function setDb()
    {
        $this->db = new mysqli(
            $this->config['host'],
            $this->config['username'],
            $this->config['password'],
            $this->config['database']
        );

        $sql = "SET
        character_set_results = 'utf8',
        character_set_client = 'utf8',
        character_set_connection = 'utf8',
        character_set_database = 'utf8',
        character_set_server = 'utf8';";

        $this->db->query($sql);
    }

    private function setTables($tables)
    {
        $this->table =
            $tables;
    }

    private function setConfig($config)
    {
        if ( isset($config['host']) ) {
            $this->config['host'] = $config['host'];
        }

        if ( isset($config['username']) ) {
            $this->config['username'] = $config['username'];
        }

        if ( isset($config['password']) ) {
            $this->config['password'] = $config['password'];
        }

        if ( isset($config['database']) ) {
            $this->config['database'] = $config['database'];
        }
    }

    private function __desctruct()
    {
        mysqli_close(
            $this->db
        );
    }
}
