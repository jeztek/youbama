import xml.dom.minidom
import urllib
import string
import random
import time
from django.core.mail import send_mail
from feedparser import _HTMLSanitizer as Sanitizer

from youbama.main.models import Video

# Set acceptable elements list
Sanitizer.acceptable_elements[:] = ['br', 'blockquote','em','strong']


def sanitize(text):
	s = Sanitizer('utf8')
	s.feed(text)
	return s.output()


# Generate random string of alphanumeric characters of a given length
def random_string(num):
	return "".join([random.choice(string.letters + string.digits) for x in xrange(num)])


# Used to retrieve text nodes when parsing XML
def getText(nodelist):
    rc = ""
    for node in nodelist:
        if node.nodeType == node.TEXT_NODE:
            rc = rc + node.data
    return rc


# Fetch video meta information using Youtube API
def get_video_info(youtube_id):
	feedUrl = "http://gdata.youtube.com/feeds/api/videos/"
	
	result = urllib.urlopen(feedUrl + youtube_id)
	resultStr = result.read()
	result.close()

	if resultStr.startswith("<?xml") == False:
		return None

	dom = xml.dom.minidom.parseString(resultStr)

	elements = dom.getElementsByTagName("title")
	title = getText(elements[0].childNodes)
	
	elements = dom.getElementsByTagName("content")
	description = getText(elements[0].childNodes)

	elements = dom.getElementsByTagName("yt:duration")
	duration = elements[0].getAttribute('seconds')

	elements = dom.getElementsByTagName("yt:statistics")
	viewcount = elements[0].getAttribute('viewCount')
	
	data = {}
	data['title'] = title
	data['description'] = description
	data['duration_seconds'] = duration
	data['duration_string'] = time.strftime("%H:%M:%S", time.gmtime(int(duration)))
	data['viewcount'] = viewcount	

	dom.unlink()
	return data


# TODO: Fill this in!
# Validate e-mail address syntax
def is_valid_email(email):
	if email.isspace() or email == '': return False
	return True


# TODO: Move hash URL out to config file
# Send validation request e-mail to uploading user
def sendmail(title, email, hash):	
	hash_url = "http://www.youbama.com/validate/" + hash

	print "Sending mail to " + email
	send_mail("Youbama.com - " + title, 'To validate your request to post on youbama.com, please click on the link below\n\n' + hash_url + '\n\nThanks!\n', 'validate@youbama.com', [email])


# Update number of replies for a particular video
# NOTE: Called whenever a video is marked as visible
#       youbama.validate, process_videos.php
def update_num_replies(id):
	replycount = Video.objects.filter(in_reply_to=id).count()
	video = Video.objects.get(id=id)
	video.replycount = replycount
	video.save()
