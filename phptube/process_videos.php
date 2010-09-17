<?php

// File: process_videos.php
// Description: Utility functions to upload and fetch metadata from YouTube

include_once("phptube.php");

# Upload video to YouTube using phptube library
# TODO: Add error handling for failed uploads
# TODO: Delete uploaded videos 
function upload() {

	$username = "youbama";
	$password = "mypassword";
	$database = "youbama";
	
	$youtube_username = "YouBamaVideos";
	$youtube_password = "mypassword";
	
	$file_prefix = "/var/www/youbama/static/videos/";
	
	$link = mysql_connect(localhost, $username, $password);
	@mysql_select_db($database, $link) or die ("Unable to select database");

	$phptube = new PHPTube($youtube_username, $youtube_password);
	
	# Find all videos that haven't been uploaded yet
	$query = "SELECT * FROM `main_upload`, `main_video` WHERE `uploaded`=false AND main_upload.video_id=main_video.id";
	$result = mysql_query($query, $link);
	$numrows = mysql_num_rows($result);

	print "Processing uploads...\n";
	
	for ($i=0; $i < $numrows; $i++) {
		$id = mysql_result($result, $i, "main_video.id");
		$file = mysql_result($result, $i, "file");
		$title = mysql_result($result, $i, "title");
		$description = mysql_result($result, $i, "description");
		
		# Upload to YouTube
		$youtube_id = $phptube->upload($file_prefix . $file, $title, "www.youbama.com youbama barack obama", $description, 25, "EN");
		print "Uploaded $file ($youtube_id)\n";
		
		# Mark file as uploaded
		$query2 = "UPDATE `main_upload`, `main_video` SET `main_video`.`youtube_id`='$youtube_id', `uploaded`=true WHERE `main_video`.`id`='$id'";
		$result2 = mysql_query($query2, $link);
	}
	mysql_close($link);
}


# Fetch YouTube video metadata from uploaded videos (duration)
function get_ytdata() {

	$username = "youbama";
	$password = "mypassword";
	$database = "youbama";

	$link = mysql_connect(localhost, $username, $password);
	@mysql_select_db($database, $link) or die ("Unable to select database");

	# Find all videos that have been uploaded but not processed
	$query = "SELECT * FROM `main_upload`, `main_video` WHERE `uploaded`=true AND `processed`=false AND `main_upload`.`video_id`=`main_video`.`id`";
	$result = mysql_query($query, $link);
	$numrows = mysql_num_rows($result);

	print "Fetching metadata...\n";
	
	for ($i=0; $i < $numrows; $i++) {
		$id = mysql_result($result, $i, "main_video.id");
		$youtube_id = mysql_result($result, $i, "youtube_id");
		print "Trying data fetch for $youtube_id\n";
		$data = @file_get_contents("http://gdata.youtube.com/feeds/api/videos/" . $youtube_id);
		if (!$data) {
			print "no data\n";
		}
		else {
			# Match found, update metadata in database
			if(preg_match("/yt:duration seconds=\'([0-9]+)\'/", $data, $matches)) {

				$timestr = strftime("%H:%M:%S", gmdate($matches[1]));
				print($id . ": " . $matches[1] . " seconds, $timestr\n");

				# If file has been uploaded, processed, and validated, mark as visible on website
				if (mysql_result($result, $i, "validated") == '1') {
					$reply_id = mysql_result($result, $i, "in_reply_to");
					if ($reply_id > 0) {
						update_num_replies($reply_id);
					}
					$query2 = "UPDATE `main_upload`, `main_video` SET `duration_seconds`=$matches[1], `duration_string`=\"$timestr\", `processed`=true, `visible`=true WHERE `main_video`.`id`=$id";
				}
				else {
					$query2 = "UPDATE `main_upload`, `main_video` SET `duration_seconds`=$matches[1], `duration_string`=\"$timestr\", `processed`=true WHERE `main_video`.`id`=$id";			
				}
				mysql_query($query2, $link);
			}
		}
	}
}

# Helper function to update the number of replies for each video
function update_num_replies($id) {
	$username = "youbama";
	$password = "mypassword";
	$database = "youbama";

	$link = mysql_connect(localhost, $username, $password);
	@mysql_select_db($database, $link) or die ("Unable to select database");

	$query = "SELECT COUNT(*) AS `replycount` FROM `main_video` WHERE `in_reply_to`=$id";
	$result = mysql_query($query, $link);
	$replycount = mysql_result($result, 0, "replycount");
	print "Setting replycount to $replycount for video id=$id\n";
	$query2 = "UPDATE `main_video` SET `replycount`=$replycount WHERE `id`=$id";
	mysql_query($query2, $link);
}

# Update the number of views for each visible video file
function update_num_views() {

	$username = "youbama";
	$password = "mypassword";
	$database = "youbama";

	$link = mysql_connect(localhost, $username, $password);
	@mysql_select_db($database, $link) or die ("Unable to select database");

	# Find all videos that have been uploaded but not processed
	$query = "SELECT * FROM `main_video` WHERE `visible`=true";
	$result = mysql_query($query, $link);
	$numrows = mysql_num_rows($result);

	print "Fetching view data...\n";
	
	for ($i=0; $i < $numrows; $i++) {
		$id = mysql_result($result, $i, "id");
		$youtube_id = mysql_result($result, $i, "youtube_id");
		print "$id ($youtube_id): ";
		$data = @file_get_contents("http://gdata.youtube.com/feeds/api/videos/" . $youtube_id);
		if (!$data) {
			print "no data\n";
		}
		else {
			# Match found, update metadata in database
			if(preg_match("/yt:statistics viewCount=\'([0-9]+)\'/", $data, $matches)) {

				print($matches[1] . " views\n");
				
				$query2 = "UPDATE `main_video` SET `viewcount`=$matches[1] WHERE `id`=$id";
				mysql_query($query2, $link);
			}
		}
	}
}

?>
