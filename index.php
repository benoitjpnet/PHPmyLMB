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
    $id = 0;
    $filesArray = array();
    /* Search for files (which are allowed_extensions) in all directories. */
    $directories = glob('*', GLOB_ONLYDIR);
    if (count($directories) == 0) {
        trigger_error(
            'No folder detected, please add at least one folder!',
            E_USER_WARNING
        );
    }
    foreach ($directories as $dir) {
        $files = glob('./' . $dir . '/' . $conf['allowed_extensions'], GLOB_BRACE);
        if (count($files) == 0) {
            trigger_error(
                "Folder $dir has no compatible media, please add at least one!",
                E_USER_WARNING
            );
        }
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
                    passthru($conf['mediainfo'] . ' "' . $file . '"', $return);
                    if ($return !=0 ) {
                        print 'Error with mediainfo, command line was: '
                            . $conf['mediainfo'] . ' "' . $file . '"';
                    }
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
                'path' => trim($file, "./"),
                'dirname' => $name[1],
                'name' => $name[2],
                'mtime' => $mtime,
                'extendedDetails' => $extendedDetails,
            );
        }
    }
    /* If no media, generate a false content. */
    if (count($filesArray) == 0) {
        $filesArray[1] = array(
            'id' => 1,
            'path' => 'error',
            'dirname' => 'error',
            'name' => 'No media detected!',
            'mtime' => time(),
            'extendedDetail' => ''
        );
    }
    /* 
     * Sorting array.
     * TODO: Can be optimized/factored...
     */
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
            $previousID = false;
            foreach ($files as $file) {
                $filesArray[$file['id']] = $file;
                /* We add a next an previous ID for navigation. */
                $nextID = next($files)['id'];
                if (is_null($nextID)) {
                    $filesArray[$file['id']]['nextid'] = false;
                } else {
                    $filesArray[$file['id']]['nextid'] = $nextID;
                }
                $filesArray[$file['id']]['previousid'] = $previousID;
                $previousID = $file['id'];
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
            $previousID = false;
            foreach ($files as $file) {
                $filesArray[$file['id']] = $file;
                /* We add a next an previous ID for navigation. */
                $nextID = next($files)['id'];
                if (is_null($nextID)) {
                    $filesArray[$file['id']]['nextid'] = false;
                } else {
                    $filesArray[$file['id']]['nextid'] = $nextID;
                }
                $filesArray[$file['id']]['previousid'] = $previousID;
                $previousID = $file['id'];
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
            $previousID = false;
            foreach ($files as $file) {
                $filesArray[$file['id']] = $file;
                /* We add a next an previous ID for navigation. */
                $nextID = next($files)['id'];
                if (is_null($nextID)) {
                    $filesArray[$file['id']]['nextid'] = false;
                } else {
                    $filesArray[$file['id']]['nextid'] = $nextID;
                }
                $filesArray[$file['id']]['previousid'] = $previousID;
                $previousID = $file['id'];
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
 * @desc Get the right media HTML code.
 *
 * @return string $mediacode HTML code for the media.
 *
 * @param $path, path to the file.
 * @param $mediatitle, title of the media.
 */
function getMediaCode($path, $mediatitle)
{
    global $conf;
    $pathinfo = pathinfo($path);
    $pathurlencoded = $conf['uri'] . '/' . rawurlencode($pathinfo['dirname']) . '/' .
        rawurlencode($pathinfo['basename']);
    $extension = $pathinfo['extension'];
    /* Handle file types. */
    switch($extension) {
    /* Video */
    case 'webm':
        /* Load VTT subtitles if any.*/
        if (file_exists('./' . $path . '.vtt')) {
            $mediacode = <<<EOT
                <video id="media" src="$pathurlencoded" controls="">
                    <track src="{$pathurlencoded}.vtt" kind="subtitles" default="">
                    Your browser doesn't support this format. Try Firefox.
                </video><br />
                [<a title="This stream has soft subtitles displayed in HTML5. Click to download the VTT file" href="{$pathurlencoded}.vtt">Download subtitles?</a>]
EOT;
        } else {
            $mediacode = <<<EOT
                <video id="media" src="$pathurlencoded" controls="">
                    Your browser doesn't support this format. Try Firefox.
                </video>
EOT;
        }
        break;
    /* Audio */
    case 'opus':
    case 'ogg':
        $mediacode = <<<EOT
            <audio id="media" src="$pathurlencoded" controls=""">
                Your browser doesn't support this format. Try Firefox.
            </audio>
EOT;
        break;
    /* PDF */
    case 'pdf':
        $mediacode = "<embed src=\"$pathurlencoded#view=Fit\">";
        break;
    /* Picture */
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'webp':
    case 'svg':
    case 'gif':
        $mediacode = <<<EOT
            <a title="$mediatitle" href="$pathurlencoded">
                <img id="media" alt="$mediatitle" src="$pathurlencoded"/>
            </a>
EOT;
        break;
    /*  */
    default:
        header("415 Unsupported Media Type");
        print "<h1>415 Unsupported Media Type</h1>";
        exit(1);
    }
    
    return $mediacode;
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
    $i = 1;
    $explorer = '';
    /* Construct "vignettes". */
    foreach ($filesArray as $file) {
        /* Open the "vignette".*/
        if ($i <= 1 || $file['dirname'] != $filesArray[$i-1]['dirname']) {
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
    header('Content-Type: application/rss+xml; charset=UTF-8');
    $date = date('r');
    print <<<EOT
<rss xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/"
xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://www.rssboard.org/media-rss" xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">
    <channel>
        <title>{$conf['title']}</title>
        <link>{$conf['uri']}</link>
        <lastBuildDate>$date</lastBuildDate>
        <atom:link href="{$conf['uri']}/?feed" rel="self" type="application/rss+xml" />
        <language>en-US</language>
        <generator>PHPmyLMB</generator>
        <description>{$conf['desc']}</description>

EOT;
    $filesArray = getFiles();
    foreach ($filesArray as $file) {
        $nameurlencoded = rawurlencode($file['name']);
        $dirnameurlencoded = rawurlencode($file['dirname']);
        $namespecial = htmlspecialchars($file['name']);
        $dirnamespecial = htmlspecialchars($file['dirname']);
        $updated = date('r', $file['mtime']);
        $name = htmlentities($file['name'], ENT_COMPAT | ENT_XML1, 'UTF-8');
        $mediacode = getMediaCode($file['path'], $file['name']);
        $entries[$file['mtime']] = <<<EOT

        <item>
            <title>$name</title>
            <dc:creator>{$conf['author']}</dc:creator>
            <pubDate>$updated</pubDate>
            <link>{$conf['uri']}/?id={$file['id']}&amp;file=$dirnameurlencoded/$nameurlencoded</link>
            <guid isPermaLink="false">{$file['id']}</guid>
            <description>
            <![CDATA[{$mediacode}]]>
            </description>
        </item>

EOT;
    }
    /* Finally return the last entries ($conf['feed_items']) for the feed.*/
    krsort($entries);
    $entries = array_slice($entries, 0, $conf['feed_items']);
    foreach ($entries as $entry) {
        print $entry;
    }
    print <<<EOT
    </channel>
</rss>
EOT;
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
    foreach ($filesArray as $file) {
        if (preg_match('/(.webm|.opus|.ogg)/i', $file['name'])) {
            $nameurlencoded = rawurlencode($file['name']);
            $dirnameurlencoded = rawurlencode($file['dirname']);
            $filename = htmlspecialchars($file['name']);
            $dirname = htmlspecialchars($file['dirname']);
            $entries[$file['mtime']] = <<<EOT

        <track>
            <title>$dirname/$filename</title>
            <location>{$conf['uri']}/$dirnameurlencoded/$nameurlencoded</location>
        </track>

EOT;
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
    $sort = $_GET['sort'];
    $filesArray = getFiles($sort);
    if (array_key_exists($id, $filesArray)) {
        $mtime = $filesArray[$id]['mtime'];
        $mtimeATOM = date(DATE_ATOM, $mtime);
        $mtimeHuman = date(DATE_RFC822, $mtime);
        $mediatitle = $filesArray[$id]['name'];
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

EOT;
        }
        $mediacode .= '</div>';
        $mediacode .= getMediaCode($filesArray[$id]['path'], $mediatitle);
        /* Create naviation link to go at next or previous media. */
        $navigation = '';
        if ($filesArray[$id]['nextid'] !== FALSE) {
            $navigation .= '<a href="?id=' . $filesArray[$id]['nextid'] . '&amp;sort=' . $sort . '">→ Next</a><br />';
        }
        if ($filesArray[$id]['previousid'] !== FALSE) {
            $navigation .= '<a href="?id=' . $filesArray[$id]['previousid'] . '&amp;sort=' . $sort . '">← Previous</a><br />';
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
$navigation = (isset($navigation)) ? $navigation : '';
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
        $navigation
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
