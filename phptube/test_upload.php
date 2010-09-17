<?php

include_once("phptube.php");


$username = "youbama";
$password = "mypassword";
$database = "youbama";

$youtube_username = "YouBamaVideos";
$youtube_password = "mypassword";

$file_prefix = "/var/www/youbama/static/videos/";
	
$link = mysql_connect(localhost, $username, $password);
@mysql_select_db($database, $link) or die ("Unable to select database");

$phptube = new PHPTube($youtube_username, $youtube_password);

$id = $argv[1];

$query = "SELECT * FROM `main_upload`, `main_video` WHERE main_upload.video_id=main_video.id AND main_video.id=$id";
$result = mysql_query($query, $link);
$numrows = mysql_num_rows($result);

print "Found $numrows results\n";

$file = mysql_result($result, 0, "file");
$file = "/var/www/youbama/static/videos/" . $file;

$title = mysql_result($result, 0, "title");
$description = mysql_result($result, 0, "description");

print "File:  $file\nTitle: $title\nDesc:  $description\n";

$youtube_id = $phptube->upload($file, $title, "www.youbama.com youbama barack obama", $description, 25, "EN");

print "Uploaded $youtube_id\n";

mysql_close($link);

?>
