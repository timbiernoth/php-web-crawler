<?php

$main = '
    <!--
    <form>
        <input type="text" value="" placeholder="*.example.com">
        <button type="submit">Let\'s Go</button>
    </form>
    -->
';

$main .= '
    <table>
        <tbody>
            <tr>
                <td>Start</td>
                <td>2018-10-01 13:58:26</td>
            </tr>
            <tr>
                <td>IPs</td>
                <td>' . ($db->getCount('ips') - 4) . '</td>
            </tr>
            <tr>
                <td>Names</td>
                <td>' . ($db->getCount('names') - 6) . '</td>
            </tr>
            <tr>
                <td>Domains</td>
                <td>' . ($db->getCount('domains')) . '</td>
            </tr>
            <tr>
                <td>Domains Crawled</td>
                <td>' . ($db->getCount('domains_crawler')) . '</td>
            </tr>
            <tr>
                <td>Pages Crawled</td>
                <td>' . ($db->getCount('pages_crawler')) . '</td>
            </tr>
            <tr>
                <td>Pages Non-Canonical</td>
                <td>' . ($db->getCount('pages_non_canonical')) . '</td>
            </tr>
            <tr>
                <td>Pages Non-Indexable</td>
                <td>' . ($db->getCount('pages_non_indexable')) . '</td>
            </tr>
            <tr>
                <td>Links hreflang</td>
                <td>' . ($db->getCount('pages_hreflang')) . '</td>
            </tr>
            <tr>
                <td>Links Non-HTTP</td>
                <td>' . ($db->getCount('pages_non_http')) . '</td>
            </tr>
            <tr>
                <td>a-Tags</td>
                <td>' . ($db->getCount('pages_a_tags')) . '</td>
            </tr>
        </tbody>
    </table>
';
