from django.shortcuts import render_to_response, get_object_or_404
from django.http import HttpResponse, HttpResponseRedirect
from django.db.models import Q

from datetime import datetime
import re

from youbama.main.models import Video, Upload, Contact
from youbama.settings import MEDIA_ROOT

from youbama.main.helpers import *


def staticpage(request):
	return HttpResponseRedirect('/home')


# TODO: Look at http://blog.awarelabs.com/?p=29 for pagination
def home(request, page='1'):
	popular_by_day_video_list = Video.objects.extra(select={'d' : "date(add_datetime)"}).filter(visible=True, in_reply_to=0).order_by('-d', '-votecount')

	if page=='1':
		featured_video_list = Video.objects.filter(Q(id=663) | Q(id=1279)).order_by('-id')
	else:
		featured_video_list = []

	return render_to_response('main/home.html', {
		'popular_by_day_video_list' : popular_by_day_video_list,
		'featured_video_list' : featured_video_list,
		'page' : page,
		'type' : 'home',	# used by pagination.html
	})


def popular(request, page='1'):
	popular_video_list = Video.objects.filter(visible=True, in_reply_to=0).order_by('-votecount')

	return render_to_response('main/popular.html', {
		'popular_video_list' : popular_video_list,
		'page' : page,
		'type' : 'popular',	# used by pagination.html
	})


def newest(request, page='1'):
	newest_video_list = Video.objects.filter(visible=True, in_reply_to=0).order_by('-add_datetime')	

	return render_to_response('main/newest.html', {
		'newest_video_list' : newest_video_list,
		'page' : page,
		'type' : 'newest',	# used by pagination.html
	})
	

def search(request, query='', page='1'):
	#if request.method == 'GET':
	if request.method == 'POST':
		query = request.POST['query']

	if (query == ''):
		search_video_list = []
	else:
		search_video_list = Video.objects.filter(Q(title__icontains=query) | Q(description__icontains=query),visible=True).order_by('-votecount')

	return render_to_response('main/search.html', {
		'search_video_list' : search_video_list,
		'query' : query,
		'page' : page,
		'type' : 'search/' + query,	# used by pagination.html
	})

	
def replies(request, id):
	replies_list = Video.objects.filter(visible=True, in_reply_to=id).order_by('add_datetime')
	return render_to_response('main/replies.html', {
		'replies_list' : replies_list
	})
		
		
def about(request):
	return render_to_response('main/about.html', { })


def upload(request, in_reply_to=0):
	video = ''
	if (in_reply_to > 0):
		video = get_object_or_404(Video, id=in_reply_to)

	if request.method == 'GET':		
		return render_to_response('main/upload.html', {
			'video' : video,
		})

	elif request.method == 'POST':
		email = sanitize(request.POST['email'])
		
		# Validate e-mail address
		if (is_valid_email(email) == False):
			error_message = "Invalid e-mail address"

			return render_to_response('main/upload.html', {
				'video' : video,
				'error_message' : error_message,
			})

		if 'file' in request.FILES:
			return handle_file_upload(request, email, in_reply_to, video)
		else:
			return handle_youtube_upload(request, email, in_reply_to, video)

	return HttpResponseRedirect('/')
	

def handle_file_upload(request, email, in_reply_to, video):
	# TODO: sanitize filename
	file = request.FILES['file']
	filename = random_string(10) + "_" + file['filename']

	# Sanity checks
	if (len(filename) > 255):
		error_message = "Filename is too long"
		return render_to_response('main/upload.html', {
			'video' : video,
			'error_message' : error_message,
		})

	title = sanitize(request.POST['title'])
	desc = sanitize(request.POST['description'])

	if ((desc == "") or (title == "")):
		error_message = "Title and description cannot be empty"
		return render_to_response('main/upload.html', {
			'video' : video,
			'error_message' : error_message,
		})
	
	fd = open('%s/videos/%s' % (MEDIA_ROOT, filename), 'wb')
	fd.write(file['content'])
	fd.close()

	c, created = Contact.objects.get_or_create(email=email, defaults={'validated' : False})
		
	# TODO: verify uniqueness	
	hash = random_string(26)
	
	v = Video.objects.create(
		user_id='1', 
		contact=c,
		
		type='1',
		add_datetime=datetime.today(), 

		title=title, 
		short_description=desc[0:130],
		description=desc, 

		duration_seconds=0, 
		duration_string="00:00:00",

		viewcount=1,
		votecount=0, 
		reportcount=0,

		in_reply_to=in_reply_to, 
		replycount=0,

		validation_hash=hash,
		visible=False,
		youtube_id=0)

	u = Upload.objects.create(
		video_id=v.id,
		file=filename,
		uploaded=False,
		processed=False,
		validated=False)

	sendmail(title, email, hash)
	
	return render_to_response('main/uploaded.html', {
		'video' : video,
	})


def handle_youtube_upload(request, email, in_reply_to, video):
	youtubeurl = request.POST['youtubeurl']

	# Extract YouTube video ID from URL
	idrule = re.compile('v=([A-Za-z0-9_-]+)')
	r = idrule.search(youtubeurl)
	if (r == None):
		error_message = "Invalid YouTube URL"
		return render_to_response('main/upload.html', {
			'video' : video,
			'error_message' : error_message,
		})

	else:
		youtube_id = r.group(1)

		video_list = Video.objects.filter(youtube_id=youtube_id)
		if len(video_list) > 0:
			error_message = "This video already exists on the site"
			return render_to_response('main/upload.html', {
				'video' : video,
				'error_message' : error_message,
			})		

		data = get_video_info(youtube_id)
		if data == None:
			error_message = "Video not found"
			return render_to_response('main/upload.html', {
				'video' : video,
				'error_message' : error_message,
			})

		c, created = Contact.objects.get_or_create(email=email, defaults={'validated' : False})

		desc = data['description']
	
		# TODO: verify uniqueness	
		hash = random_string(26)

		v = Video.objects.create(
			user_id='1', 
			contact=c,

			type='2',
			add_datetime=datetime.today(), 

			title=data['title'], 
			short_description=desc[0:130],
			description=desc, 

			duration_seconds=data['duration_seconds'], 
			duration_string=data['duration_string'],

			viewcount=data['viewcount'],
			votecount=0, 
			reportcount=0,

			in_reply_to=in_reply_to, 
			replycount=0,

			validation_hash=hash,
			visible=False,
			youtube_id=youtube_id)

		sendmail(data['title'], email, hash)

	return render_to_response('main/uploaded.html', {
		'video' : video,
	})


def voteup(request, id):
	v = get_object_or_404(Video, id=id)
	v.votecount = v.votecount + 1
	v.save()
	return HttpResponse("<em>Votes: </em>" + str(v.votecount))


def report(request, id):
	v = get_object_or_404(Video, id=id)
	v.reportcount = v.reportcount + 1
	v.save()
	return HttpResponse("<em>Reported!</em>")


# TODO: Create index on hash
def validate(request, hash):
	video_list = Video.objects.filter(validation_hash=hash)
	if len(video_list) > 0:
		v = video_list[0]

		# Validate contact
		c = Contact.objects.get(id=v.contact_id)
		c.validated = True
		c.save()
		
		# If this is an uploaded video:
		if v.type == 1:
			upload_list = Upload.objects.filter(video=v)
			if len(upload_list) > 0:
				u = upload_list[0]

				u.validated = True
				u.save()
				
				if (u.uploaded == True and u.processed == True):
					v.visible = True
					v.save()
	
					if (v.in_reply_to > 0):
						update_num_replies(v.in_reply_to)

		# If this is a youtube video:
		elif v.type == 2:
			v.visible = True
			v.save()

			if (v.in_reply_to > 0):
				update_num_replies(v.in_reply_to)
		
		error_message = ''
		return render_to_response('main/validated.html', {
			'video' : v,
		})
		
	else:
		return render_to_response('main/validation_failed.html', {
		})

