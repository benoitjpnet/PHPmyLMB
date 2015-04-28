<?php
/*
 * Default configuration file for PHPmyLMB.
 * You should not edit directly this file, but copy it as config.local.php.
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
    'allowed_extensions' => '*.{webm,opus,ogg,webp,png,gif,jpg,jpeg,svg,pdf}',
    /*
     * Path to mediainfo binary. Used to obtains details about media.
     * If PHPmyLMB cannot access to mediainfo binary, extended details is
     * not activated.
     */
     'mediainfo' => '/usr/bin/mediainfo',
    /*
     * 2 files are used for cache, cache_asc and cache_mtime.
     * Need to be writable!
     * If you want to clear the cache, just delete cache_* files.
     */
    'cache_enabled' => true,
    'cache_path' => './cache_',
    'cache_expire' => 1800, // Validity of the cache in seconds.
    /*
     * Theme related stuff.
     */
    'author' => 'your name',
    'title' => 'PHPmyLMB – Lightweight Media Browser',
    'desc' => "yourName's Media",
    'feed_items' => 30,
    'header' => '<a href="https://github.com/benpro/PHPmyLMB"><img style="position: absolute; top: 0; right: 0; border: 0;" src="forkme.png" alt="Fork me on GitHub"></a>',
    'footer' => 'Powered by <a href="https://github.com/benpro/PHPmyLMB">PHPmyLMB</a>.',
);
/* Set wanted timezone. */
date_default_timezone_set('UTC');
/*
 * Have filenames with UTF-8 characters?
 * Be sure to have a locale with UTF-8 support installed on your server and
 * use it with setlocale php's function.
 * Default to en_US.UTF-8.
 */
setlocale(LC_CTYPE, 'en_US.UTF-8');