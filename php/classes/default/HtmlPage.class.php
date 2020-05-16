<?php

class HtmlPage
{
    private $configDomain = [
        'assets' => [
            'css' => [],
            'js' => [],
        ],
    ];

    private $configPage = [
        'html' => [
            'classes' => '',
            'language' => 'en',
        ],
        'head' => [
            'title' => 'Default',
            'description' => '',
            'charset' => 'utf-8',
            'robots' => 'noindex,nofollow',
            'favicon' => 'favicon.ico',
            'viewport' => 'width=device-width',
        ],
        'body' => [
            'classes' => '',
            'main' => '',
            'header' => '',
            'footer' => '',
            'order' => [
                'header',
                'main',
                'footer',
            ],
        ],
        'assets' => [ // TODO: top, bottom
            'css' => [],
            'js' => [],
        ],
        'exit' => true,
    ];

    public function __construct($config_domain = [])
    {
        $this->setConfigDomain($config_domain);
    }

    public function render($config_page)
    {
        $this->setConfigPage($config_page);

        $output = $this->getHtml();

        if ($this->configPage['exit'] !== false) {
            $this->renderHeaders([
                'content-type:text/html;charset=' . $this->configPage['head']['charset'],
                'content-language:' . $this->configPage['html']['language'],
            ]);
            echo $output;
            exit;
        }

        return $output;
    }

    private function getHtml()
    {
        $language = '';
        if ( ! empty($this->configPage['html']['language']) ) {
            $language = ' lang="' . $this->configPage['html']['language'] . '"';
        }

        $html_classes = '';
        if ( ! empty($this->configPage['html']['classes']) ) {
            $html_classes = ' class="' . $this->configPage['html']['classes'] . '"';
        }

        $body_classes = '';
        if ( ! empty($this->configPage['body']['classes']) ) {
            $body_classes = ' class="' . $this->configPage['body']['classes'] . '"';
        }

        $html = '<!doctype html><html' . $language . $html_classes . '>
            <head>
                ' . $this->getHead() . '
            </head>
            <body' . $body_classes . '>
                ' . $this->getBody() . '
            </body>
        </html>';

        return $html;
    }

    private function getHead()
    {
        $html = '
            <title>' . $this->configPage['head']['title'] . '</title>
            <meta charset="' . $this->configPage['head']['charset'] . '">
            ' . $this->getHeadViewport() . '
            ' . $this->getHeadRobots() . '
            ' . $this->getHeadDescription() . '
            ' . $this->getHeadFavicon() . '
        ';

        return $html;
    }

    private function getHeadViewport()
    {
        $html = '';

        if ($this->configPage['head']['viewport'] !== '') {
            $html = '<meta name="viewport" content="' . $this->configPage['head']['viewport'] . '">';
        }

        return $html;
    }

    private function getBody()
    {
        $html = '';

        foreach ($this->configPage['body']['order'] as $by) {
            $html .= $this->getBodyContent($by);
        }

        $html .= '
            ' . $this->getStylesheets() . '
            ' . $this->getJavascripts() . '
        ';

        return $html;
    }

    private function getBodyContent($by) // TODO: Asides
    {
        if ($by == 'main') {
            return $this->getBodyMain();
        } else if ($by == 'header') {
            return $this->getBodyHeader();
        } else if ($by == 'footer') {
            return $this->getBodyFooter();
        }
    }

    private function getBodyMain()
    {
        $html = '';

        if ($this->configPage['body']['main'] !== '') {
            $html = '
                <main class="page-main">
                    ' . $this->configPage['body']['main'] . '
                </main>
            ';
        }

        return $html;
    }

    private function getBodyHeader()
    {
        $html = '';

        if ($this->configPage['body']['header'] !== '') {
            $html = '
                <header class="page-header">
                    ' . $this->configPage['body']['header'] . '
                </header>
            ';
        }

        return $html;
    }

    private function getBodyFooter()
    {
        $html = '';

        if ($this->configPage['body']['footer'] !== '') {
            $html = '
                <footer class="page-footer">
                    ' . $this->configPage['body']['footer'] . '
                </footer>
            ';
        }

        return $html;
    }

    private function getHeadDescription()
    {
        $description = $this->configPage['head']['description'];

        if ($description !== '') {
            $description = '<meta name="description" content="' . $description . '">';
        }

        return $description;
    }

    private function getHeadRobots()
    {
        $robots = '';
        $robots_origin =
            str_replace(' ', '',
                trim(
                    strtolower(
                        $this->configPage['head']['robots'])));

        if ($robots_origin !== '') {
            if (strpos($robots_origin, ',') !== false) {
                foreach (explode(',', $robots_origin) as $directive) {
                    if ($directive !== 'index' &&
                        $directive !== 'follow'&&
                        $directive !== '') {
                        $robots .= $directive . ',';
                    }
                }
                $robots = substr($robots, 0, -1);
            } else {
                if ($robots_origin !== 'index' &&
                    $robots_origin !== 'follow' &&
                    $robots_origin !== '') {
                    $robots = $robots_origin;
                }
            }
            if ( ! empty($robots)) {
                $robots = '<meta name="robots" content="' . $robots . '">';
            }
        }

        return $robots;
    }

    private function getHeadFavicon()
    {
        $favicon = $this->configPage['head']['favicon'];

        if ($favicon !== '') {
            $favicon = '<link rel="x-icon" href="' . $favicon . '">';
        }

        return $favicon;
    }

    private function getStylesheets()
    {
        $stylesheets = '';
        $stylesheets_domain = $this->configDomain['assets']['css'];
        $stylesheets_page = $this->configPage['assets']['css'];

        if ( ! empty($stylesheets_domain) ||
             ! empty($stylesheets_page)) {
            $stylesheets .= '<div class="stylesheets">' . "\n";
            if ( ! empty($stylesheets_domain) ) {
                foreach ($stylesheets_domain as $url) {
                    $stylesheets .= '<link rel="stylesheet" href="' . $url . '">' . "\n";
                }
            }
            if ( ! empty($stylesheets_page) ) {
                foreach ($stylesheets_page as $url) {
                    $stylesheets .= '<link rel="stylesheet" href="' . $url . '">' . "\n";
                }
            }
            $stylesheets .= '</div>' . "\n";
        }

        return $stylesheets;
    }

    private function getJavascripts()
    {
        $javascripts = '';
        $javascripts_domain = $this->configDomain['assets']['js'];
        $javascripts_page = $this->configPage['assets']['js'];

        if ( ! empty($javascripts_domain) ||
             ! empty($javascripts_page)) {
            $javascripts .= '<div class="javascripts">' . "\n";
            if ( ! empty($javascripts_domain) ) {
                foreach ($javascripts_domain as $url) {
                    $javascripts .= '<script src="' . $url . '"></script>' . "\n";
                }
            }
            if ( ! empty($javascripts_page) ) {
                foreach ($javascripts_page as $url) {
                    $javascripts .= '<script src="' . $url . '"></script>' . "\n";
                }
            }
            $javascripts .= '</div>' . "\n";
        }

        return $javascripts;
    }

    private function renderHeaders($headers)
    {
        foreach ($headers as $header) {
            header($header);
        }
    }

    private function setConfigPage($config_page)
    {
        if ( isset($config_page['html']['classes']) ) {
            $this->configPage['html']['classes'] = $config_page['html']['classes'];
        }

        if ( isset($config_page['html']['language']) ) {
            $this->configPage['html']['language'] = $config_page['html']['language'];
        }

        if ( isset($config_page['head']['title']) ) {
            $this->configPage['head']['title'] = $config_page['head']['title'];
        }

        if ( isset($config_page['head']['description']) ) {
            $this->configPage['head']['description'] = $config_page['head']['description'];
        }

        if ( isset($config_page['head']['charset']) ) {
            $this->configPage['head']['charset'] = $config_page['head']['charset'];
        }

        if ( isset($config_page['head']['robots']) ) {
            $this->configPage['head']['robots'] = $config_page['head']['robots'];
        }

        if ( isset($config_page['head']['favicon']) ) {
            $this->configPage['head']['favicon'] = $config_page['head']['favicon'];
        }

        if ( isset($config_page['head']['viewport']) ) {
            $this->configPage['head']['viewport'] = $config_page['head']['viewport'];
        }

        if ( isset($config_page['body']['classes']) ) {
            $this->configPage['body']['classes'] = $config_page['body']['classes'];
        }

        if ( isset($config_page['body']['main']) ) {
            $this->configPage['body']['main'] = $config_page['body']['main'];
        }

        if ( isset($config_page['body']['header']) ) {
            $this->configPage['body']['header'] = $config_page['body']['header'];
        }

        if ( isset($config_page['body']['footer']) ) {
            $this->configPage['body']['footer'] = $config_page['body']['footer'];
        }

        if ( isset($config_page['body']['order']) ) {
            $this->configPage['body']['order'] = $config_page['body']['order'];
        }

        if ( isset($config_page['assets']['css']) ) {
            $this->configPage['assets']['css'] = $config_page['assets']['css'];
        }

        if ( isset($config_page['assets']['js']) ) {
            $this->configPage['assets']['js'] = $config_page['assets']['js'];
        }

        if ( isset($config_page['exit']) ) {
            $this->configPage['exit'] = $config_page['exit'];
        }
    }

    private function setConfigDomain($config_domain)
    {
        if ( isset($config_domain['assets']['css']) ) {
            $this->configDomain['assets']['css'] = $config_domain['assets']['css'];
        }

        if ( isset($config_domain['assets']['js']) ) {
            $this->configDomain['assets']['js'] = $config_domain['assets']['js'];
        }
    }
}
