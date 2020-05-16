<?php

class PagesUrl
{
    public $url = '';
    public $ignore = [
        'parameters' => [],
        'pathes' => [],
        'urls' => [],
    ];

    public $error = false;
    public $errorMassage = '';

    public function __construct($url)
    {
        $this->url = $url;
        $this->setUrl();
    }

    private function setUrl()
    {

        if ( isset($_GET['url']) && $this->url == '' ) {
            $this->url = $_GET['url'];
        }

        if ( ! empty($this->url) ) {

            $url_tolower = strtolower($this->url);

            if (
                (
                    substr($url_tolower, 0, 8) === 'https://' ||
                    substr($url_tolower, 0, 7) === 'http://'
                ) && str_replace(['https://', 'http://'], '', $url_tolower) !== ''
            ) {

                $this->url = $this->url;

                if ( isset($_GET['ignore_parameters']) && ! empty($_GET['ignore_parameters']) ) {
                    $this->ignore['parameters'] = $_GET['ignore_parameters'];
                }

                if ( isset($_GET['ignore_pathes']) && ! empty($_GET['ignore_pathes']) ) {
                    $this->ignore['pathes'] = $_GET['ignore_pathes'];
                }

                if ( isset($_GET['ignore_urls']) && ! empty($_GET['ignore_urls']) ) {
                    $this->ignore['urls'] = $_GET['ignore_urls'];
                }

            } else {
                $this->error = true;
                $this->errorMassage .= ' No valid URL!';
            }

        } else {
            $this->error = true;
            $this->errorMassage .= ' No URL!';
        }

    }

}
