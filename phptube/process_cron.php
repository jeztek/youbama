<?php

// File: process_cron.php
// Description: Script intended to run on a cron job to periodically
//              upload videos and fetch metadata from YouTube
// 		Ensures that only copy is running at a time

include_once('process_videos.php');

# File used as lock to prevent multiple instances from running concurrently
$lockfile = "/tmp/phptube_lock";

# Timeout to override existing process
$timeout_seconds = 7200;	// 2 hours


if (file_exists($lockfile)) {
	$locktime = filemtime($lockfile);
	$timediff = time() - $locktime;

	// Process timed out - kill old process 
	if ($timediff > $timeout_seconds) {
		$fh = fopen($lockfile, 'r');
		$pid = fread($fh, filesize($lockfile));
		fclose($fh);
		unlink($lockfile);
		exec("kill -KILL $pid");
		print "ERROR: Process timed out after $timediff seconds, killing PID $pid\n";
	}

	// Process is valid and locked
	else {
		print "Process is locked!\n";
		exit(0);
	}		
}

$fh = fopen($lockfile, 'w');
fwrite($fh, getmypid());
fclose($fh);

print "-- " . date("D M j G:i:s T Y") . " --\n";
upload();
get_ytdata();
print "\n";

unlink($lockfile);

?>
