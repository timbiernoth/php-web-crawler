<?php

class LoadTime
{
    public $output = 0;

    private $start = 0;
    private $stop = 0;

    public function start()
    {
        $this->start = get_microtime();
    }

    public function stop()
    {
        $this->stop = get_microtime();

        $this->setOutput();
    }

    private function setOutput()
    {
        $this->output = round(($this->stop - $this->start), 2);
    }
}
