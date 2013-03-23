<?php
/*
 * PHPmyLMP, a Lightweight media browser.
 * Generate a web page with media contents such as videos and audios.
 * Very useful to share with people :)
 * Forked from mitsumedia (https://github.com/mitsukarenai/mitsumedia) and
 * *really* redesigned code part.
 * LICENCE is WTFPL.
 */

require('config.inc.php');

/**
 * @desc Construct an array with all files found.
 * @return array $filesArray
 */
function getFiles() {
    
    global $conf;
    /* Find all dir and in each dir get files. */
    $directories = glob('*', GLOB_ONLYDIR);
    foreach ($directories as $dir) {
        $files = glob('./' . $dir . '/' . $conf['allowed_extensions'], GLOB_BRACE);
        foreach ($files as $file) {
            $name = explode("/", $file);
            $filesValues[] = array(
                'name' => $name[2],
                'mtime' => filemtime($file),
            );
        }
        $filesArray[$dir] = $filesValues;
        unset($filesValues);
    }
    return $filesArray;
}

/**
 * @desc Contruct the explorer HTML part.
 * @return string $explorer HTML code to insert.
 */
function explorerHTML() {
    
    $filesArray = getFiles();
    $explorer = '';
    foreach ($filesArray as $dirname => $files) {
        $dirnameurlencoded = rawurlencode($dirname);
        $explorer .= <<<EOT
<div class="vignette">
    <div class="title">$dirname/</div>
        <ul>
        
EOT;
        foreach($files as $file) {
            $filenameurlencoded = rawurlencode($file['name']);
            $explorer .= <<<EOT
            <li>
            <a href="$dirnameurlencoded/$filenameurlencoded"><img title="Right click → Save as" alt="" src="save.png"></a>
            <a href="?file=$dirnameurlencoded/$filenameurlencoded">{$file['name']}</a>
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
    <link rel="self" type="application/atom+xml" href="http://{$conf['uri']}/?feed" />
    <id>tag:phpmylmb,2000:1</id>
    <updated>$date</updated>

EOT;
    $filesArray = getFiles();
    foreach ($filesArray as $dirname => $files) {
        foreach ($files as $file) {
            $nameurlencoded = rawurlencode($file['name']);
            $dirnameurlencoded = rawurlencode($dirname);
            $entries[$file['mtime']] = <<<EOT
            
    <entry>
        <title>$dirname/{$file['name']}</title>
        <link href="http://{$conf['uri']}/?file=$dirnameurlencoded/$nameurlencoded"/>
        <id>http://{$conf['uri']}/?file=$dirnameurlencoded/$nameurlencoded</id>
        <updated>{$file['mtime']}</updated>
        <author><name>{$conf['author']}</name></author><summary>{$file['name']}</summary>
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

/* File part. When user has clicked on a file. Generate the embeded media. */
if (isset($_GET['file'])) {
    $path = urldecode($_GET['file']);
    /* Verify if the file exists and construct the embedded media. */
    if (file_exists('./' . $path)) {
        $mtime = date('c', filemtime($path));
        $mediatitle = $path;
        $pathurlencoded = rawurlencode($path);
        $mediacode = <<<EOT
<div class="fileinfo">
    File: <time datetime="$mtime">$path</time><br />
    Added: $mtime
</div>

EOT;
        if (strpos($path, '.webm')) {
            $mediacode .= '<video id="media1" src="' . $pathurlencoded  .'" controls autoplay>Your browser doesn\'t support this format. Try Firefox.</video>';
        } elseif (strpos($path, '.opus') || strpos($path, '.ogg')) {
            $mediacode .= '<audio id="media1" src="' . $pathurlencoded  .'" controls autoplay>Your browser doesn\'t support this format. Try Firefox.</audio>';
        }
    } else {
        header("HTTP/1.0 404 File not found");
        print "<h1>404 File not found</h1>";
        exit(1);
    }
}

/* Below the HTML part. */
$mediacode = (isset($mediacode)) ? $mediacode : '';
$mediatitle = (isset($mediatitle)) ? $mediatitle : 'Home';
print <<<EOT
<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>{$conf['title']} – $mediatitle</title>
    <meta name="description" content="{$conf['desc']}">
    <link rel='stylesheet' href='style.css' type='text/css' media='screen' />
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <meta name="robots" content="index" />
</head>
<body>
<a href="https://github.com/benpro/PHPmyLMB"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png" alt="Fork me on GitHub"></a>
    <div id="content">
        <div style="min-height:150px;">
            <h1>{$conf['title']}</h1>
            <span style="font-size:small">{$conf['desc']}</span><br>
            <div id="mediacode">
                $mediacode
            </div>
        </div>
        
EOT;
        /* Construct the "explorer". */
        print explorerHTML();
print <<<EOT
    </div>
    <div id="footer">
    Powered by <a href="https://github.com/benpro/PHPmyLMB">PHPmyLMB</a>.
    </div>
</body>
</html>
EOT;
?>
