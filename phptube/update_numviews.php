<?php

// File: update_numviews.php
// Description: Script intended to run on a cron job to periodically
//		fetch the number of views for each video from YouTube 
//		and update the database with the new values

include_once('process_videos.php');

print "-- " . date("D M j G:i:s T Y") . " --\n";
update_num_views();
print "\n";

?>