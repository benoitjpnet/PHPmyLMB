<?php
/*
 * PHPmyLMB is a lightweight media browser.
 * It generates a web page with media contents such as videos and audios.
 * Very useful to share with people :)
 * Project initially forked from @mitsukarenai.
 * LICENCE is WTFPL.
 */

/* Load the default config file or the local one. */
if(file_exists('config.local.php')) {
    require 'config.local.php';
} else {
    require 'config.default.php';
}

/* Check if mediainfo binary is accessible. */
if ($conf['extendedDetails'] && !file_exists($conf['mediainfo'])) {
    $conf['extendedDetails'] = false;
    trigger_error(
        'Extended details is disabled. No access to mediainfo binary!
        Install mediainfo on your server or disable extendedDetails in configuration.',
        E_USER_WARNING
    );
}

/**
 * @desc Construct an array with all files found.
 *
 * @param string $sort Mode used for sorting files.
 *  Default: asc
 *  Can be: asc, desc or mtime
 *
 * @return array $filesArray
 */
function getFiles($sort = 'asc')
{
    global $conf;
    /* If results are cached and cache not expired, use it. */
    if ($conf['cache_enabled'] && is_readable($conf['cache_path'] . $sort)) {
        if ((time() - filemtime($conf['cache_path'] . $sort)) <= $conf['cache_expire']) {
            $fileCache = fopen($conf['cache_path'] . $sort, 'r');
            $contents = fread($fileCache, filesize($conf['cache_path'] . $sort));
            fclose($fileCache);

            return unserialize($contents);
        }
    }
    $id=0;
    /* Search for files (which are allowed_extensions) in all directories. */
    $directories = glob('*', GLOB_ONLYDIR);
    foreach ($directories as $dir) {
        $files = glob('./' . $dir . '/' . $conf['allowed_extensions'], GLOB_BRACE);
        foreach ($files as $file) {
            $name = explode("/", $file);
            $mtime = filemtime($file);
            if ($conf['extendedDetails'] === true) {
                /*
                * Obtain extended details with mediainfo binary and save it to
                * .info file if it doesn't exists.
                */
                if (!file_exists($file . '.info')) {
                    ob_start();
                    $fileEscaped = escapeshellcmd($file);
                    $fileEscaped = str_replace(' ', '\ ', $fileEscaped);
                    passthru($conf['mediainfo'] . ' ' .  $fileEscaped);
                    $fileInfo = fopen($file .  '.info', 'w');
                    fwrite($fileInfo, ob_get_contents());
                    fclose($fileInfo);
                    ob_end_clean();
                }
                /* Read .info file. Cached forever. */
                $fileInfo = fopen($file . '.info', 'r');
                $extendedDetails = fread($fileInfo, filesize($file . '.info'));
                fclose($fileInfo);
            } else {
                $extendedDetails = false;
            }
            /* Store obtained details about the media. */
            $id++;
            $filesArray[$id] = array(
                'id' => $id,
                'path' => $file,
                'dirname' => $name[1],
                'name' => $name[2],
                'mtime' => $mtime,
                'extendedDetails' => $extendedDetails,
            );
        }
    }
    /* Sorting array. Default, sorted ASC by glob(). */
    if ($sort == 'mtime') {
        foreach ($filesArray as $file) {
            /* We can have conflict in mtime key, so be sure to not have
             * conflict by adding seconds... :(
             */
            if (isset($tempArray) && (array_key_exists($file['dirname'], $tempArray))) {
                while (array_key_exists($file['mtime'], $tempArray[$file['dirname']])) {
                    $file['mtime']++;
                }
            }
            $tempArray[$file['dirname']][$file['mtime']] = $file;
        }
        unset($filesArray);
        foreach ($tempArray as $dirname => $files) {
            krsort($tempArray[$dirname]);
        }
        foreach ($tempArray as $files) {
            foreach ($files as $file) {
                $filesArray[$file['id']] = $file;
            }
        }
    }
    if ($sort == 'desc') {
        foreach ($filesArray as $file) {
            $tempArray[$file['dirname']][$file['name']] = $file;
        }
        unset($filesArray);
        foreach ($tempArray as $dirname => $files) {
            krsort($tempArray[$dirname]);
        }
        foreach ($tempArray as $files) {
            foreach ($files as $file) {
                $filesArray[$file['id']] = $file;
            }
        }
    }
    if ($sort == 'asc') {
        foreach ($filesArray as $file) {
            $tempArray[$file['dirname']][$file['name']] = $file;
        }
        unset($filesArray);
        foreach ($tempArray as $dirname => $files) {
            ksort($tempArray[$dirname]);
        }
        foreach ($tempArray as $files) {
            foreach ($files as $file) {
                $filesArray[$file['id']] = $file;
            }
        }
    }
    /* Store results in the cache & return it. */
    if ($conf['cache_enabled']) {
        $fileCache = fopen($conf['cache_path'] . $sort, 'w');
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
    } elseif (isset($_GET['sort']) && ($_GET['sort'] == 'desc')) {
        $sort = 'desc';
    } else {
       $sort = 'asc';
    }
    $filesArray = getFiles($sort);
    $filesCount = count($filesArray);
    $i=1;
    $explorer = '';
    /* Construct "vignettes". */
    foreach ($filesArray as $file) {
        /* Open the "vignette".*/
        if ($i <= 2 || $file['dirname'] != $filesArray[$i-1]['dirname']) {
            $dirnameurlencoded = rawurlencode($file['dirname']);
            $explorer .= <<<EOT

    <div class="vignette">
        <div class="title">{$file['dirname']}/</div>
        <ul>

EOT;
        }
        /* Set files in the vignette. */
        $filenameurlencoded = rawurlencode($file['name']);
        $explorer .= <<<EOT

            <li>
                <a href="$dirnameurlencoded/$filenameurlencoded"><img title="Right click → Save as" alt="" src="save.png"></a>
                <a href="?id={$file['id']}&amp;file=$dirnameurlencoded/$filenameurlencoded&amp;sort=$sort">{$file['name']}</a>
            </li>

EOT;
        /* Close the vignette if no more files in it. */
        if ($i == $filesCount || $file['dirname'] != $filesArray[$i+1]['dirname']) {
            $explorer .= <<<EOT

        </ul>
    </div>

EOT;

        }
    $i++;
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
                $filename = htmlspecialchars($file['name']);
                $dirname = htmlspecialchars($dirname);
                $entries[$file['mtime']] = <<<EOT

            <track>
                <title>$dirname/$filename</title>
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
if (isset($_GET['id'])) {

    /* Verify if the id/file exists and construct the embedded media. */
    $id = $_GET['id'];
    $filesArray = getFiles();
    if (array_key_exists($id, $filesArray)) {
        $mtime = $filesArray[$id]['mtime'];
        $mtimeATOM = date(DATE_ATOM, $mtime);
        $mtimeHuman = date(DATE_RFC822, $mtime);
        $mediatitle = $filesArray[$id]['name'];
        $pathinfo = pathinfo($filesArray[$id]['path']);
        $pathurlencoded = rawurlencode($pathinfo['dirname']) . '/' . rawurlencode($pathinfo['basename']);
        $mediacode = <<<EOT

            <div class="fileinfo">
                File: <time datetime="$mtimeATOM">{$filesArray[$id]['path']}</time><br />
                Added: $mtimeHuman <br />
EOT;
        if ($conf['extendedDetails'] === true) {
            $mediacode .= <<<EOT
                <a href="javascript:toggle('info');">
                    Detailed informations (click to toggle).
                </a>
                <div id="info" class="hidden">
                    <pre>
                        {$filesArray[$id]['extendedDetails']}
                    </pre>
                </div>
            </div>

EOT;
        }
        /* Handle file types. */
        switch($pathinfo['extension']) {
        /* Video */
        case 'webm':
            /* Load VTT subtitles if any.*/
            if (file_exists('./' . $filesArray[$id]['path'] . '.vtt')) {
                $mediacode .= "\t\t\t" . '<video id="media" src="' . $pathurlencoded  .'" controls="" autoplay=""><track src="' . $pathurlencoded . '.vtt" kind="subtitles" default>Your browser doesn\'t support this format. Try Firefox.</video><br>[<a title="This stream has soft subtitles displayed in HTML5. Click to download the VTT file" href="' . $pathurlencoded . '.vtt">Download subtitles?</a>]';
            } else {
                $mediacode .= "\t\t\t" . '<video id="media" src="' . $pathurlencoded  .'" controls="" autoplay="">Your browser doesn\'t support this format. Try Firefox.</video>';
            }
            break;
        /* Audio */
        case 'opus':
        case 'ogg':
            $mediacode .= "\t\t\t" . '<audio id="media" src="' . $pathurlencoded  .'" controls="" autoplay="">Your browser doesn\'t support this format. Try Firefox.</audio>';
            break;
        /* PDF */
        case 'pdf':
            $mediacode .= "\t\t\t" . '<embed src="' . $pathurlencoded  .'#view=Fit">';
            break;
        /* Picture */
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'webp':
        case 'svg':
        case 'gif':
            $mediacode .= "\t\t\t" . '<a title="' . $mediatitle . '" href="' . $pathurlencoded . '"><img id="media" alt="' . $mediatitle . '" src="' . $pathurlencoded . '"/></a>';
            break;
        /*  */
        default:
            header("415 Unsupported Media Type");
            print "<h1>415 Unsupported Media Type</h1>";
            exit(1);
        }
    } else {
        header("404 Not Found");
        print "<h1>404 Not Found</h1>";
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
        } if (isset($_GET['sort']) && $_GET['sort'] == 'desc') {
            $options .= "\t\t\t" . '<option value="?sort=desc" selected="">Descending</option>';
        } else {
            $options .= "\t\t\t" . '<option value="?sort=desc">Descending</option>';
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
<script type="text/javascript">
    function toggle(divID) {
        var item = document.getElementById(divID);
        if (item) {
            item.className=(item.className=='hidden')?'unhidden':'hidden';
        }
    }
</script>
</body>
</html>
EOT;
