<?php
/*
 * Configuration file for PHPmyLMB.
 */
$conf = array(
    /*
     * Set URI of your PHPmyLMB installation. Do not set the last trailing
     * slash!
     */
    'uri' => 'http://example.com/media',
    /*
     * Allowed extensions. Warning adding an extension will not automagically
     * works, because the code will not handle it!
     */
    'allowed_extensions' => '*.{webm,opus,ogg,webp,png,gif,jpg,jpeg,svg}',
    'author' => 'your name',
    'title' => 'PHPmyLMP – Lightweight Media Browser',
    'desc' => "yourName's Media",
    'feed_items' => 30,
    'header' => '<a href="https://github.com/benpro/PHPmyLMB"><img style="position: absolute; top: 0; right: 0; border: 0;" src="forkme.png" alt="Fork me on GitHub"></a>',
    'footer' => 'Powered by <a href="https://github.com/benpro/PHPmyLMB">PHPmyLMB</a>.',
);
/* Set wanted timezone. */
date_default_timezone_set('UTC');