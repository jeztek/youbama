<?php

// ************************************************************************
// Class      PHPTube
// Version:   0.1.7
// Date:      2008/08/10
// Author:    Michael Kamleitner (michael.kamleitner@gmail.com)
// WWW:       http://www.kamleitner.com/code
//            (suggestions, bug-reports & general shouts are welcome)
// Copyright: copyright 2007 - Michael Kamleitner
//
//            This file is part of PHPTube
//
//            PHPTube is free software; you can redistribute it and/or modify
//            it under the terms of the GNU General Public License as published by
//            the Free Software Foundation; either version 2 of the License, or
//            (at your option) any later version.
//
//            PHPTube is distributed in the hope that it will be useful,
//            but WITHOUT ANY WARRANTY; without even the implied warranty of
//            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//            GNU General Public License for more details.
//
//            You should have received a copy of the GNU General Public License
//            along with PHPTube; if not, write to the Free Software
//            Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//            
// ************************************************************************

require_once 'HTTP/Client.php';
require_once 'HTTP/Request.php';
require_once 'HTTP/Client/CookieManager.php';

class PHPTube {

	var $cookies;
	var $mgr;
	var $req; 
	var $debug = true;
	var $auth = false;
	
	// Function:	PHPTube   ... Initialize PHPTube-Object
	// Paramters:	$username ... YouTube Accountname (if empty, upload is disabled)
	//						$password ... YouTube Passwort (if empty, upload is disabled)
	//						$debug	  ... Debug-Flag
	
	function PHPTube ($username = "", $password = "", $debug = false) {
		
		if ($username != "" && $password != "") {
			$url = "http://www.youtube.com/login?username=".$username."&password=".$password."&next=/index&current_form=loginForm&action_login=1";
			$this->debug = $debug;
			$this->req =& new HTTP_Request($url);
			$this->req->addHeader("User-Agent","Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");	
			$this->mgr = new HTTP_Client_CookieManager();
			$response = $this->req->sendRequest();
			if (PEAR::isError($response)) {
			    echo $response->getMessage()."\n";
			} else {
				$this->auth = true;
			  $this->cookies = $this->req->getResponseCookies();   
				$success = false; 
				foreach ($this->cookies as $c) {	
					if ($c["name"]=="LOGIN_INFO" && $c["value"]<>"")
						$success = true;
				}
				if (!$success)
					die ("Login failed!\n");	
			}	
		}
	}
	
	// Function:	download  ... Download any Video-Clip from YouTube
	// Paramters:	$video_id ... Video-ID as given in YouTube URL (f.e. the Video at http://youtube.com/watch?v=TWZ5j-SNVKs
	//							  has the ID "TWZ5j-SNVKs"
	//				$video_filename ... local path+filename, the video is downloaded to (check write-permissions!)
	
	function download ($video_id, $video_filename) {
		$url = "http://www.youtube.com/watch?v=".$video_id;
		$this->req =& new HTTP_Request($url);
		$response = $this->req->sendRequest();
		if (PEAR::isError($response)) {
			echo $response->getMessage()."\n";
		} else {	
			$page = $this->req->getResponseBody();	
			//preg_match('/watch_fullscreen\?video_id=(.*?)&l=(.*?)+&t=(.*?)&/', $page, $match);
			preg_match('/"video_id": "(.*?)"/', $page, $match);
			$var_id = $match[1];

			preg_match('/"t": "(.*?)"/', $page, $match);
			$var_t = $match[1];

			$url = "";
			$url .= $var_id;
			$url .= "&t=";
			$url .= $var_t;
			$url = "http://www.youtube.com/get_video?video_id=".$url;
			if ($this->debug)
				print $url."\n";
			$req =& new HTTP_Request($url,array ("allowRedirects"=>true, "maxRedirects"=> 99));
			$req->setMethod(HTTP_REQUEST_METHOD_GET);
			$req->addHeader("User-Agent","Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");	
			$response = $req->sendRequest();
			if (PEAR::isError($response)) {
			    //echo $response->getMessage()."\n";
				print "Error: Failed to open YouTube-Source\n";					    
			} else {
                		if ($o = fopen ($video_filename, "w")) {
					fwrite($o,$req->getResponseBody());
					fclose ($o);
                    			print "Download done! File: ".$video_filename."\n";
				} else { 
					print "Error: Failed to open target-file\n";
        			}
			}
		}
	}		
	
	// Function:	upload    ... Upload Video-Clip to YouTube
	// Returns:	    		  	... alphanumeric ID of the new Clip
	// Paramters:	$video_filename ... local path & filename of the Clip which is to be uploaded
	//						$video_title ... Video-Title (String)
	//						$video_tags ... Tags (String, Separator = Blank)
	//						$video_description ... Description (String)
	//						$video_category ... Category (2=Autos & Vehicles, 23=Comedy, 24=Entertainment,
	//																  				1=Film & Animation, 20=Gadgets & Games, 26=Howto & DIY,	
	//											  									10=Music, 25=News & Politics, 22=People & Blogs,
	//																				  15=Pets & Animals, 17=Sports 19=Travel & Places)
	//						$video_language ("DE", "EN"...)
	//						$public (true / false)
	// 						$family (true / false) - Not Supported anymore
	// 						$friends (true / false) - Not Supported anymore
			
	function upload ($video_filename, $video_title, $video_tags, $video_description, $video_category, $video_language, $public=true, $family=true, $friends=true) 
	{
		if ($this->auth) {
			if (file_exists($video_filename)) {
					
				$url = "http://www.youtube.com/my_videos_upload";
				$this->req =& new HTTP_Request($url);
				$this->req->setMethod(HTTP_REQUEST_METHOD_POST);
				$this->req->addHeader("User-Agent","Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");	
				$this->req->addPostData("field_myvideo_title",$video_title);
				$this->req->addPostData("field_myvideo_keywords",$video_tags);
				$this->req->addPostData("field_myvideo_descr",$video_description);
				$this->req->addPostData("language",$video_language);
				$this->req->addPostData("field_myvideo_categories",$video_category);
				$this->req->addPostData("ignore_broadcast_settings","0");
				$this->req->addPostData("action_upload","1");
				
				if ($public) {					
					$this->req->addPostData("field_privacy","public");		
				} else {
					$this->req->addPostData("field_privacy","private");
					
					// Seems like the Family/Friends-option isn't existing anymore
					
					//if ($family && $friends) 
					//	$this->req->addPostData("share_list","Family,Friends");
					//elseif ($family)
					//	$this->req->addPostData("share_list","Family");
					//elseif ($friends)
					//	$this->req->addPostData("share_list","Friends");
				}
					
				foreach ($this->cookies as $c) {
					$this->mgr->addCookie ($c);
				}
				$this->mgr->passCookies ($this->req);
				$response = $this->req->sendRequest();				
				if (PEAR::isError($response)) {
				    die ("Error: ".$response->getMessage()."\n");
				} else {
					
					$p = strpos($this->req->getResponseBody(),"id=\"theForm\"");
					$p = strrpos(substr($this->req->getResponseBody(),0,$p),"<form");
					$p = $p + strpos(substr($this->req->getResponseBody(),$p),"action=\"") + 8;
					$url = substr($this->req->getResponseBody(),$p,strpos(substr($this->req->getResponseBody(),$p),"\""));
					
					$p = strpos($this->req->getResponseBody(),"name=\"addresser\" value=\"");
					$addresser = substr($this->req->getResponseBody(),$p+24,strpos(substr($this->req->getResponseBody(),$p+24),"\""));
					
					if ($this->debug) {
						print "action: ".$url."\n";
						print "addresser: ".$addresser."\n";
					}
					$this->req =& new HTTP_Request($url);
					$this->req->addHeader("User-Agent","Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
					$this->req->setMethod(HTTP_REQUEST_METHOD_POST);
					$this->req->addPostData("field_command","myvideo_submit");
					$this->req->addPostData("field_myvideo_title",$video_title);
					$this->req->addPostData("field_myvideo_keywords",$video_tags);
					$this->req->addPostData("field_myvideo_descr",$video_description);
					$this->req->addPostData("language",$video_language);
					$this->req->addPostData("field_myvideo_categories",$video_category);
					$this->req->addPostData("action_upload","1");
					$this->req->addPostData("addresser",$addresser);
					
					if ($public) {					
						$this->req->addPostData("field_privacy","public");		
					} else {
						$this->req->addPostData("field_privacy","private");
						//if ($family && $friends) 
						//	$this->req->addPostData("share_list","Family,Friends");
						//elseif ($family)
						//	$this->req->addPostData("share_list","Family");
						//elseif ($friends)
						//	$this->req->addPostData("share_list","Friends");
					}
					if ($this->debug)
						print "file: ".$video_filename."\n";
					$this->req->addFile("field_uploadfile",$video_filename);		
					$this->mgr->passCookies ($this->req);
					$response = $this->req->sendRequest();
					if (PEAR::isError($response)) {
				    		echo "Error: ".$response->getMessage()."\n" ;
					} else {
			
						if ($this->debug)
							print "Upload OK - ".$response."!\n";
						
						$this->req =& new HTTP_Request("http://www.youtube.com/my_videos");
						$this->mgr->passCookies ($this->req);
						$response = $this->req->sendRequest();
						if (PEAR::isError($response)) {
				    	echo $response->getMessage()."\n";
						} else {
							$p = strpos($this->req->getResponseBody(),"'EditVideos', ['");
							$video_id = substr($this->req->getResponseBody(),$p+16,
									   strpos(substr($this->req->getResponseBody(),$p+16),"']"));			
							print "Upload done! ID: ".$video_id."\n";
							return $video_id;
						}
					}
				}
			} else {
				print "Error: local file not found!\n";
			}
		} else {
			print "Error: not authenticated!\n";
		}
	}
	
	// Function:	edit      ... edit metadata of existing YouTube-Clip
	// Returns:	    		  	... -
	// Paramters:	$video_id ... alphanumeric YouTube-ID of Clip
	//						$video_title ... Video-Title (String)
	//						$video_tags ... Tags (String, Separator = Blank)
	//						$video_description ... Description (String)
	//						$video_category ... Category (for details see upload-function)
	//						$video_language ("DE", "EN"...)
	//						$public (true / false)

	// Warning: edit-method not finished yet! 

	function edit ($video_id, $video_title, $video_tags, $video_description, $video_category, $video_language, $public=true) {
		
		$url = "http://www.youtube.com/my_videos_edit?ns=1&next=/my_videos&video_id=".$video_id;
		$this->req =& new HTTP_Request($url);
		$this->req->setMethod(HTTP_REQUEST_METHOD_GET);
		$this->req->addHeader("User-Agent","Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");					
		foreach ($this->cookies as $c) {
			$this->mgr->addCookie ($c);
		}
		$this->mgr->passCookies ($this->req);
		
		$response = $this->req->sendRequest();				
		if (PEAR::isError($response)) {			
		    die ("Error: ".$response->getMessage()."\n");
		} else {
			$p = strpos($this->req->getResponseBody(),"token = \"");
			$p = substr($this->req->getResponseBody(), $p+9, strpos(substr($this->req->getResponseBody(), $p+9), "\""));			
		}
		
		if ($this->debug) {
			print "session token: ".$p."\n";
			print "video-id: ".$video_id."\n";
		}
		$url = "http://www.youtube.com/my_videos_edit";
		$this->req =& new HTTP_Request($url);
		$this->req->setMethod(HTTP_REQUEST_METHOD_POST);
		$this->req->addHeader("User-Agent","Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");	
		$this->req->addPostData("ns","1");
		$this->req->addPostData("next","/my_videos");
		$this->req->addPostData("video_id",$video_id);
		$this->req->addPostData("action_videosave","");
		$this->req->addPostData("field_current_still_id","2");
		$this->req->addPostData("field_still_id","2");
		$this->req->addPostData("field_myvideo_title",$video_title);
		$this->req->addPostData("field_myvideo_descr",$video_description);
		$this->req->addPostData("field_myvideo_categories",$video_category);
		$this->req->addPostData("field_myvideo_keywords",$video_tags);		
		//$this->req->addPostData("video_play","");
		//$this->req->addPostData("language",$video_language);		
		$this->req->addPostData("ignore_broadcast_settings","0");					
		$this->req->addPostData("session_token",$p);
		
		$this->req->addPostData("field_date_mon","");
		$this->req->addPostData("field_date_day","");
		$this->req->addPostData("field_date_yr","");
		$this->req->addPostData("location","");

		if ($public) {					
			$this->req->addPostData("field_privacy","public");		
		} else {
			$this->req->addPostData("field_privacy","private");						
		}
			
		$this->mgr->passCookies ($this->req);
		
		
		$response = $this->req->sendRequest();				
		if (PEAR::isError($response)) {
		    die ("Error: ".$response->getMessage()."\n");
		} else {
			print $this->req->getResponseBody();
		}
	}

	// Function:	getLast   ... retrieve ID of latest YouTube-Clip of the current User
	// Returns:	    		  	... alphanumeric YouTube-ID of latest Clip

	function getLast ()
	{
		if ($this->auth) {
                        $this->req =& new HTTP_Request("http://www.youtube.com/my_videos");
			foreach ($this->cookies as $c) {
                        	$this->mgr->addCookie ($c);
                        }
                        $this->mgr->passCookies ($this->req);
                        $response = $this->req->sendRequest();
                        if (PEAR::isError($response)) {
	                        echo $response->getMessage()."\n";
                        } else {
                           	$p = strpos($this->req->getResponseBody(),"'EditVideos', ['");
                                $video_id = substr($this->req->getResponseBody(),$p+16,
                                                   strpos(substr($this->req->getResponseBody(),$p+16),"']")); 
			    	if ($this->debug) 
                            		print "Last Uploaded video-ID is: ".$video_id."\n";
			    	return $video_id;
			}
		} else {
			print "Error: not authenticated!\n";
		}
	}
}
?>
