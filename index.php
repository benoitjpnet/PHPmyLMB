<?php

if(isset($_GET['sitemap']))
	{
	header('Content-Type: application/xml');
	echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
	$directory = "./";
	$files = glob($directory . "*");
	foreach($files as $file)
		{
		if(is_dir($file))
			{
			$dir2 = "$file/";
			$files2 = glob($dir2 . "*");
			foreach($files2 as $file2)
				{
				$fileout = explode("/", $file2);
				$filepath = substr("$file", 2); $special = '';
				if(strpos($fileout[2], '.webm') == FALSE) { $paramv = ''; } else { $special = "1"; $paramv = '?video='; }
				if(strpos($fileout[2], '.opus') == FALSE) { $parama = ''; } else { $special = "1"; $parama = '?audio='; }
				if(strpos($fileout[2], '.swf') == FALSE) { $paramf = ''; } else { $special = "1"; $paramf = '?flash='; }
				if(strpos($fileout[2], '.gif') == FALSE) { $parami = ''; } else { $special = "1"; $parami = '/?image='; }
				echo "<url>\n";
				echo '	<loc>http://'.$_SERVER['SERVER_NAME'].'/'.$paramv.$parama.$paramf.rawurlencode($filepath)."/".rawurlencode($fileout[2])."</loc>\n";
				echo '	<lastmod>'.date('c', filemtime($file2))."</lastmod>\n";
				echo "</url>\n";
				}
			}
		}
	echo '</urlset>';
	die;
	}

if(isset($_GET['feed']))
	{
	header('Content-Type: application/atom+xml; charset=UTF-8');
	echo '<?xml version="1.0" encoding="utf-8"?><feed xmlns="http://www.w3.org/2005/Atom"><title type="text">Mitsu\'Media</title><link rel="self" type="application/atom+xml" href="http://'.$_SERVER['SERVER_NAME']. '/?feed'.'" /><id>tag:mitsu,2000:1</id><updated>'.date('c').'</updated>'."\n";
	$directory = "./";
	$files = glob($directory . "*");
	foreach($files as $file)
		{
		if(is_dir($file))
			{
			$dir2 = "$file/";
			$files2 = glob($dir2 . "*");
			foreach($files2 as $file2)
				{
				$modtime = filemtime($file2);
				$fileout = explode("/", $file2);
				$filepath = substr("$file", 2); $special = '';
				if(strpos($fileout[2], '.webm') == FALSE) { $paramv = ''; } else { $paramv = '?video='; }
				if(strpos($fileout[2], '.opus') == FALSE) { $parama = ''; } else { $parama = '?audio='; }
				if(strpos($fileout[2], '.swf') == FALSE) { $paramf = ''; } else { $paramf = '?flash='; }
				if(strpos($fileout[2], '.gif') == FALSE) { $parami = ''; } else { $parami = '/?image='; }
				$entry = "<entry>\n";
				$entry = $entry.'	<title>'.$filepath.'/'.$fileout[2].'</title>'."\n";
				$entry = $entry.'	<link href="http://'.$_SERVER['SERVER_NAME'].'/'.$paramv.$parama.$paramf.rawurlencode($filepath)."/".rawurlencode($fileout[2])."\"/>\n";
				$entry = $entry.'	<id>http://'.$_SERVER['SERVER_NAME'].'/'.$paramv.$parama.$paramf.rawurlencode($filepath)."/".rawurlencode($fileout[2]).'</id>'."\n";
				$entry = $entry.'	<updated>'.date('c', filemtime($file2))."</updated>\n";
				$entry = $entry.'	<author><name>Mitsu</name></author><summary>'.$fileout[2].'</summary>'."\n";
				$entry = $entry."</entry>\n";
				$listing[$modtime]="$entry";

				}
			}
		}
	krsort($listing);$i=0; foreach($listing as $entry) { if ($i < 20) { echo $entry;$i=1+$i; } }
	echo '</feed>';
	die;
	}

function source($a)
{
$a=explode('ˆ',$a);
$a=$a[1];
$b=explode('-', $a);
	if ($b[0] == 'YT')	{return "<a href=\"http://www.youtube.com/user/".$b[1]."/videos\">Youtube</a>";}
	else if ($a == 'AMV')	{return "<a href=\"http://www.a-m-v.org\">A-M-V</a>";}
	else if ($a == '4c')	{return "<a href=\"http://4chan.org\">4chan</a>";}
	else if ($a == 'ni')	{return "<a href=\"http://www.nicovideo.jp/\">Nico Nico Douga</a>";}
	else if ($a == 'le')	{return "<a href=\"http://www.lelombrik.net\">LeLombrik</a>";}
	else	{return "?";}
}

if(isset($_GET['video']))
	{
	if(file_exists($_GET['video']))
		{$mediatitle = $_GET['video'];$source=source($mediatitle);$mediacode = '<div class="fileinfo">Fichier: <time datetime="'.date("c", filemtime($mediatitle)).'">'.$mediatitle.'</time><br>Ajouté: '.date ("d/m/Y", filemtime($mediatitle)).'<br>Source: '.$source.'</div><video id="media1" src="'.$mediatitle.'" controls autoplay></video><span id="looplink"><a href="#" title="Lire en boucle !!" onclick="loopezMoiCa()">∞</a></span>';}
	else {header("HTTP/1.0 410 Gone"); echo 'fichier non trouvé, <a href="/">retourner à la racine</a>'; die;}
	}

else if(isset($_GET['flash']))
	{
	if(file_exists($_GET['flash']))
		{$mediatitle = $_GET['flash'];$source=source($mediatitle);$mediacode = '<div class="fileinfo">Fichier: <time datetime="'.date("c", filemtime($mediatitle)).'">'.$mediatitle.'</time><br>Ajouté: '.date ("d/m/Y", filemtime($mediatitle)).'<br>Source: '.$source.'</div><embed src="'.$mediatitle.'" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash">';}
	else {header("HTTP/1.0 410 Gone");echo 'fichier non trouvé, <a href="/">retourner à la racine</a>';die;}
	}

else if(isset($_GET['audio']))
	{
	if(file_exists($_GET['audio']))
		{if(isset($_GET['loop'])){$loop=' loop';}else{$loop='';}$mediatitle = $_GET['audio'];$source=source($mediatitle);$mediacode = '<div class="fileinfo">Fichier: <time datetime="'.date("c", filemtime($mediatitle)).'">'.$mediatitle.'</time><br>Ajouté: '.date ("d/m/Y", filemtime($mediatitle)).'<br>Source: '.$source.'</div><audio id="media1" src="'.$mediatitle.'" controls autoplay></audio><span id="looplink"><a href="#" title="Lire en boucle !!" onclick="loopezMoiCa()">∞</a></span>';}
	else {header("HTTP/1.0 410 Gone");echo 'fichier non trouvé, <a href="/">retourner à la racine</a>';die;}
	}

else if(isset($_GET['image']))
	{
	if(file_exists($_GET['image']))
		{$mediatitle = $_GET['image'];$source=source($mediatitle);$mediacode = '<div class="fileinfo">Fichier: <time datetime="'.date("c", filemtime($mediatitle)).'">'.$mediatitle.'</time><br>Ajouté: '.date ("d/m/Y", filemtime($mediatitle)).'<br>Source: '.$source.'</div><img class="file" alt="" src="'.$mediatitle.'">';}
	else {header("HTTP/1.0 410 Gone");echo 'fichier non trouvé, <a href="/">retourner à la racine</a>';die;}
	}

else { $mediatitle = 'Mitsu\'Media'; $mediacode = '';}

if ($_SERVER['HTTPS'] == "on")
	{$ssl = '<img alt="" src="lock.png">';$index='noindex';	}
else {$ssl = '<a title="Passer en HTTPS" href="https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'"><img alt="" src="lockn.png"></a>';$index='index';	} 

$mediatitle=explode('ˆ', $mediatitle);$mediatitle=$mediatitle[0];


?>

<!DOCTYPE html>
<head><meta charset="UTF-8"><title><?php echo $mediatitle; ?></title><meta name="description" content="<?php echo $mediatitle; ?>">
<style type="text/css">
<!--
body {color:#444;background-color:white;background-image:linear-gradient(#fff, #b8f1f3);background-image:-webkit-linear-gradient(#fff, #b8f1f3);background-attachment:fixed;font-size:1em;margin-left:20px;margin-top:20px;font-family:sans-serif;font-weight:bold;}
#content {background-image:url(miku_nendoroid.png);background-repeat:no-repeat;background-position:right top;}
a { color:black;text-decoration:none;font-size:small;}
a:hover { background-color:yellow; text-decoration:none;}
h1 {color:black;font-size:2em;margin:0;padding:0;}
video {border:1px solid;height:480px;box-shadow: 0px 3px 5px rgba(100, 100, 100, 0.9);}
img.file {border:1px solid;box-shadow: 0px 3px 5px rgba(100, 100, 100, 0.9);}
embed {border:1px solid;width:800px;height:480px;box-shadow: 0px 2px 6px rgba(100, 100, 100, 0.9);}
			.vignette { width:27em;height:10em;overflow-y:auto;overflow-x:hidden;float:left;margin:0 0 1em 1em; padding:5px;border: 2px solid #423137;border-radius:4px;background-color:#fdfbe1;}
			.vignette .title { font-size: 12pt;color:#830000;}
			.vignette ul { padding:0;list-style:none}
			.fileinfo {margin:1em;font-family:monospace;}
#looplink a {font-size:x-large;}
-->
</style>

<link rel="icon" href="favicon.ico" type="image/x-icon"><link rel="shortcut icon" href="favicon.ico" type="image/x-icon"> 
<meta name="robots" content="<?php echo $index; ?>,noodp,noydir,noarchive" />
</head>
<body>
<?php echo $ssl; ?> <a href="./?sitemap">sitemap</a> <a href="./?feed"><img alt="rss" width="13" height="13" src="data:image/gif;base64,R0lGODlhDQANAOYAAAAAAP/////63f/yzP/er//04//brP/t1f/Yqv+0ZP++d//Ii//Sn/+oU//Eh//Gjv/LlP95AP99Av98BP99BP9/B/99Cv+FFf+cP//Hkv/Urf9zAP91AP93AP95Bf94B/+IJf9sAP9vAP9xAP5yBv91B/51CP9/Hv+3gf9pAP9qAP9rAP5sBv5vDP91Ev99IPF2H/+VSuqOTv9gAP9lAftnCf9sDO5nFe1qGP9cAP9eAPxeBPllEO1nHu1qHvFzKeiOW/+wgv+7lf/Jq/9ZAPtWAPNRAPdYCvBZCvNfEupcFe1hF/x/QfxQAPlRAPZNAO9NAOpbG/u3lv/g0vtIAPZIAPNKAOxOCelRFO1VFetVGvNqMO6AU/ZEAPI/AOo/AOg+AOxFCehPF+ZUIOc6AOlDC+5pQN1vSu6BX8+aieI+EOpKHOwxAOotAOcsAOMtANwsAOJBF+Z3W9wqBdw0EtgmB9g7HtQdAOV9b9UUANEXANcgDeBRQf///wAAAAAAACH5BAEAAH0ALAAAAAANAA0AAAeogH1nZF9QSDUsJCYeMH1pZWFHVV1EKiIbFB0yY2xDAQcoLjMjExURLVheQgGsAQobEhMrPFFOOzYvEKwEIyo6SUpOGgEPGxesGU1PWUtEDK0YFqxMbWI9KiolDqwnMQFTbmo+IR8NEgkBBU+sa3A4IQgBAzkCAVtSAXJ3NxwLAQZUggTggiYAHj1AIkQAQcOKFjNx6PDZY6fPjxRFjFwB82ZOnTx0+gQCADs%3D"> feed</a>
<div id="content">
<div style="min-height:150px;">
<h1>Mitsu'Media: Miku</h1><span style="font-size:small">Répertoire media de <a href="http://www.suumitsu.eu">Mitsu</a> avec plein de choses bien</span><br>
<div id="mediacode">
<?php echo $mediacode; ?>
</div>
</div>
<?php
$directory = "./";
$files = glob($directory . "*");
foreach($files as $file)
{
 if(is_dir($file) )
 {
 echo "<div class=\"vignette\"><div class=\"title\">".substr($file, 2)."/</div>\n<ul>";
  $dir2 = "$file/";
  $files2 = glob($dir2 . "*");
   foreach($files2 as $file2)
	{ 
	if(is_dir($file2) == TRUE) { } else {
	$fileout = explode("/", $file2);
	$filepath = substr("$file", 2); $special = '';
	if(strpos($fileout[2], '.webm') == FALSE) { $paramv = ''; } else { $special = "1"; $paramv = '/?video='; }
	if(strpos($fileout[2], '.opus') == FALSE) { $parama = ''; } else { $special = "1"; $parama = '/?audio='; }
	if(strpos($fileout[2], '.swf') == FALSE) { $paramf = ''; } else { $special = "1"; $paramf = '/?flash='; }
	if(strpos($fileout[2], '.gif') == FALSE) { $parami = ''; } else { $special = "1"; $parami = '/?image='; }
	echo '<li>'; if($special == "1") { echo '<a href="'.rawurlencode($filepath)."/".rawurlencode($fileout[2]).'"><img title="Clic droit > Enregistrer sous" alt="" src="save.png"></a> '; }
	echo '<a href="'.$paramv.$parama.$paramf.$parami.rawurlencode($filepath)."/".rawurlencode($fileout[2]).'">'.$fileout[2]."</a></li>\n"; } }
 echo "</ul></div>\n";
 }
}
?>
</div>
<script type="text/javascript"> 
var myMedia=document.getElementById("media1"); 
function loopezMoiCa()
{ 
  myMedia.loop=true; 
  document.getElementById('looplink').style.display= 'none';
} 
</script>
<?php if($_SERVER['SERVER_NAME'] === 'media.suumitsu.eu') { ?>
<!-- Piwik -->
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://piwik.suumitsu.eu/" : "http://piwik.suumitsu.eu/");
document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
</script><script type="text/javascript">
try {
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 10);
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
} catch( err ) {}
</script><noscript><p><img src="http://piwik.suumitsu.eu/piwik.php?idsite=10" style="border:0" alt="" /></p></noscript>
<!-- End Piwik Tracking Code -->
<?php } ?>
</body>
</html>
