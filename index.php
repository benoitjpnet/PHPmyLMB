<?php
/*
 * PHPmyLMB is a lightweight media browser.
 * It generates a web page with media contents such as videos and audios.
 * Very useful to share with people :)
 * Project initially forked from @mitsukarenai.
 * LICENCE is WTFPL.
 */

if(file_exists('config.local.php')) {
    require 'config.local.php';
} else {
    require 'config.default.php';
}

/**
 * @desc Construct an array with all files found.
 *
 * @param string $sort Mode used for sorting files.
 *  Default: asc
 *  Can be: asc or mtime
 *
 * @return array $filesArray
 */
function getFiles($sort = 'asc')
{
    global $conf;
    /* If results are cached and cache not expired, use it. */
    if ($conf['cache_enabled'] && is_readable($conf['cache_path'].$sort)) {
        if ((time() - filemtime($conf['cache_path'].$sort)) <= $conf['cache_expire']) {
            $fileCache = fopen($conf['cache_path'].$sort, 'r');
            $contents = fread($fileCache, filesize($conf['cache_path'].$sort));
            fclose($fileCache);

            return unserialize($contents);
        }
    }
    /* Search for files (which are allowed_extensions) in all directories. */
    $directories = glob('*', GLOB_ONLYDIR);
    foreach ($directories as $dir) {
        $files = glob('./' . $dir . '/' . $conf['allowed_extensions'], GLOB_BRACE);
        foreach ($files as $file) {
            $name = explode("/", $file);
            $mtime = filemtime($file);
            $filesValues[$mtime] = array(
                'name' => $name[2],
                'mtime' => $mtime,
            );
        }
        if ($sort == 'mtime') {
            krsort($filesValues);
            $filesArray[$dir] = $filesValues;
        } else { // Default to ascending.
            $filesArray[$dir] = $filesValues;
        }
        unset($filesValues);
    }
    /* Store results in the cache & return it. */
    if ($conf['cache_enabled']) {
        $fileCache = fopen($conf['cache_path'].$sort, 'w');
        if ($fileCache !== false) {
            fwrite($fileCache, serialize($filesArray));
            fclose($fileCache);
        } else {
            trigger_error(
                'Cache is enabled but the file used for cache cannot be written!',
                E_USER_WARNING
            );
        }
    }

    return $filesArray;
}

/**
 * @desc Contruct the explorer HTML part.
 * @return string $explorer HTML code to generate.
 */
function explorerHTML()
{
    /* Sort can be only mtime or asc. */
    if (isset($_GET['sort']) && ($_GET['sort'] == 'mtime')) {
        $sort = 'mtime';
    } else {
       $sort = 'asc';
    }
    $filesArray = getFiles($sort);
    $explorer = '';
    foreach ($filesArray as $dirname => $files) {
        $dirnameurlencoded = rawurlencode($dirname);
        $explorer .= <<<EOT

    <div class="vignette">
        <div class="title">$dirname/</div>
            <ul>

EOT;
        foreach ($files as $file) {
            $filenameurlencoded = rawurlencode($file['name']);
            $explorer .= <<<EOT

                <li>
                    <a href="$dirnameurlencoded/$filenameurlencoded"><img title="Right click → Save as" alt="" src="save.png"></a>
                    <a href="?file=$dirnameurlencoded/$filenameurlencoded&amp;sort=$sort">{$file['name']}</a>
                </li>

EOT;
        }
        $explorer .= <<<EOT

        </ul>
    </div>

EOT;
    }

    return $explorer;
}

/* Feed part. */
if (isset($_GET['feed'])) {
    header('Content-Type: application/atom+xml; charset=UTF-8');
    $date = date('c');
    print <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title type="text">{$conf['title']}</title>
    <link rel="self" type="application/atom+xml" href="{$conf['uri']}/?feed" />
    <id>tag:phpmylmb,2000:1</id>
    <updated>$date</updated>

EOT;
    $filesArray = getFiles();
    foreach ($filesArray as $dirname => $files) {
        foreach ($files as $file) {
            $nameurlencoded = rawurlencode($file['name']);
            $dirnameurlencoded = rawurlencode($dirname);
            $updated = date(DATE_ATOM, $file['mtime']);
            $name = htmlentities($file['name'], ENT_COMPAT | ENT_XML1, 'UTF-8');
            $entries[$file['mtime']] = <<<EOT

    <entry>
        <title type="html">$dirname/$name</title>
        <link href="{$conf['uri']}/?file=$dirnameurlencoded/$nameurlencoded"/>
        <id>{$conf['uri']}/?file=$dirnameurlencoded/$nameurlencoded</id>
        <updated>$updated</updated>
        <author><name>{$conf['author']}</name></author>
        <summary type="html">$name</summary>
    </entry>

EOT;
        }
    }
    /* Finally return the last entries ($conf['feed_items']) for the feed.*/
    krsort($entries);
    $entries = array_slice($entries, 0, $conf['feed_items']);
    foreach ($entries as $entry) {
        print $entry;
    }
    print '</feed>';
    exit(0);
}

/* XSPF playlist part. */
if (isset($_GET['playlist'])) {
    (isset($_GET['sort'])) ? $sort = $_GET['sort'] : $sort = 'asc';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="playlist.xspf"');
    print <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<playlist xmlns="http://xspf.org/ns/0/" xmlns:vlc="http://www.videolan.org/vlc/playlist/ns/0/" version="1">
<title>Playlist of {$conf['title']}</title>
    <trackList>
EOT;

    $filesArray = getFiles();
    foreach ($filesArray as $dirname => $files) {
        foreach ($files as $file) {
            if (preg_match('/(.webm|.opus|.ogg)/i', $file['name'])) {
                $nameurlencoded = rawurlencode($file['name']);
                $dirnameurlencoded = rawurlencode($dirname);
                $entries[$file['mtime']] = <<<EOT

            <track>
                <title>$dirname/{$file['name']}</title>
                <location>{$conf['uri']}/$dirnameurlencoded/$nameurlencoded</location>
            </track>

EOT;
            }
        }
    }
    if ($sort == 'mtime') {
        krsort($entries);
    } // Else, default is asc mode.
    foreach ($entries as $entry) {
        print $entry;
    }
    print <<<EOT

    </trackList>
</playlist>
EOT;
    exit(0);
}

/*
 * File viewing part. When user has clicked on a file.
 * Generates the embedded media.
 */
if (isset($_GET['file'])) {
    $path = urldecode($_GET['file']);
    /* Verify if the file exists and construct the embedded media. */
    if (file_exists('./' . $path)) {
        $mtime = filemtime($path);
        $mtimeATOM = date(DATE_ATOM, $mtime);
        $mtimeHuman = date(DATE_RFC822, $mtime);
        $mediatitle = $path;
        $pathinfo = pathinfo($path);
        $pathurlencoded = rawurlencode($pathinfo['dirname']) . '/' . rawurlencode($pathinfo['basename']);
        $mediacode = <<<EOT

            <div class="fileinfo">
                File: <time datetime="$mtimeATOM">$path</time><br />
                Added: $mtimeHuman
            </div>

EOT;
        /* Handle file types. */
        if (strpos($path, '.webm')) {
            $mediacode .= "\t\t\t" . '<video id="media" src="' . $pathurlencoded  .'" controls="" autoplay="">Your browser doesn\'t support this format. Try Firefox.</video>';
        } elseif (preg_match('/(.opus|.ogg)/i', $path)) {
            $mediacode .= "\t\t\t" . '<audio id="media" src="' . $pathurlencoded  .'" controls="" autoplay="">Your browser doesn\'t support this format. Try Firefox.</audio>';
        } elseif (strpos($path, '.pdf')) {
            $mediacode .= "\t\t\t" . '<embed src="' . $pathurlencoded  .'#view=Fit">';
        } elseif (preg_match('/(.jpg|.jpeg|.png|.webp|.svg|.gif)/i', $path)) {
            $mediacode .= "\t\t\t" . '<a title="' . $mediatitle . '" href="' . $pathurlencoded . '"><img id="media" alt="' . $mediatitle . '" src="' . $pathurlencoded . '"/></a>';
        }
    } else {
        header("HTTP/1.1 404 File not found");
        print "<h1>404 File not found</h1>";
        exit(1);
    }
}

/* Below, the main HTML part. */
$stylefile = (file_exists('style.local.css')) ? 'style.local.css' : 'style.default.css';
$mediacode = (isset($mediacode)) ? $mediacode : '';
$mediatitle = (isset($mediatitle)) ? $mediatitle : 'Home';
print <<<EOT
<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>{$conf['title']} – $mediatitle</title>
    <meta name="description" content="{$conf['desc']}" />
    <link rel='stylesheet' href='{$stylefile}' type='text/css' media='screen' />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=yes">
    <link rel="icon" type="image/png" href="favicon.png" />
    <link rel="alternate" type="application/atom+xml" title="ATOM last uploaded files" href="{$conf['uri']}/?feed" />
    <meta name="robots" content="index" />
</head>
<body>
<div id="header">
    {$conf['header']}
</div>
<div id="content">
    <div style="min-height:150px;">
        <h1>{$conf['title']}</h1>
        <span style="font-size:small">{$conf['desc']}</span><br>
        <div id="mediacode">
            $mediacode
        </div>
    </div>

EOT;
        /* User can choose to sort files. */
        $options = '';
        if (isset($_GET['sort']) && $_GET['sort'] == 'asc') {
            $options .= '<option value="?sort=asc" selected="">Ascending</option>' ."\n";
        } else {
            $options .= '<option value="?sort=asc">Ascending</option>' ."\n";
        } if (isset($_GET['sort']) && $_GET['sort'] == 'mtime') {
            $options .= "\t\t\t" . '<option value="?sort=mtime" selected="">Last uploaded files</option>';
        } else {
            $options .= "\t\t\t" . '<option value="?sort=mtime">Last uploaded files</option>';
        }
        print <<<EOT

    <small>
        Sort by:
        <select onChange="if (this.value) window.location.href=this.value">
            $options
        </select><br />
        Download the <a href="?playlist">playlist</a> in XSPF format.<br />
        You can also download the <a href="?playlist&amp;sort=mtime">playlist</a> sorted by "last uploaded files".
    </small>
    <br />

EOT;
        /* Construct the "explorer". */
        print explorerHTML();
        print <<<EOT

</div>
    <div id="footer">
        {$conf['footer']}
    </div>
</body>
</html>
EOT;
