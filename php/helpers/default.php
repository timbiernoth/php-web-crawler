<?php

////////////////////////////////////////////////////////////////////////////////

if ( ! function_exists('pre') ) {
    function pre($input)
    {
        if (isset($input)) {
            echo "\n" . '<pre>' . "\n";
            print_r($input);
            echo "\n" . '</pre>' . "\n";
        } else {
            return false;
        }
    }
}

if ( ! function_exists('prexit') ) {
    function prexit($input)
    {
        pre($input);
        exit;
    }
}

////////////////////////////////////////////////////////////////////////////////

if ( ! function_exists('arr_to_str') ) {
    function arr_to_str($input, $the_key = false, $end = ' ')
    {
        $output = '';

        foreach ($input as $key => $value) {
            if ($the_key !== false) {
                $output .= $key . $end;
            } else {
                $output .= $value . $end;
            }
        }

        $output = substr($output, 0, -(strlen($end)));

        return $output;
    }
}

////////////////////////////////////////////////////////////////////////////////

if ( ! function_exists('get_datetime') ) {
    function get_datetime($format = 'Y-m-d H:i:s')
    {
        $date = new DateTime();
        return $date->format($format);
    }
}

if ( ! function_exists('get_microtime') ) {
    function get_microtime()
    {
        $temp = microtime();
        $temp = explode(' ', $temp);
        $time = $temp[1] + $temp[0];

        return $time;
    }
}

////////////////////////////////////////////////////////////////////////////////

if ( ! function_exists('json') ) {
    function json($input)
    {
        header('Content-Type:application/json;charset=utf-8');
        echo json_encode($input);
        exit;
    }
}

////////////////////////////////////////////////////////////////////////////////
