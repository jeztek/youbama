from django.conf.urls.defaults import *

urlpatterns = patterns('youbama.main.views',
	# Page retrieval
	(r'^$', 'staticpage'),

	(r'^home/$', 'home'),
	(r'^home/(?P<page>\w+)/$', 'home'),

	(r'^dhome/$', 'home'),
	(r'^dhome/(?P<page>\w+)/$', 'home'),

	(r'^popular/$', 'popular'),
	(r'^popular/(?P<page>\w+)/$', 'popular'),

	(r'^dpopular/$', 'popular'),
	(r'^dpopular/(?P<page>\w+)/$', 'popular'),

	(r'^newest/$', 'newest'),
	(r'^newest/(?P<page>\w+)/$', 'newest'),

	(r'^dnewest/$', 'newest'),
	(r'^dnewest/(?P<page>\w+)/$', 'newest'),

	# for inital search and POST request
	(r'^search/$', 'search'),
	# Note that page value is required
	(r'^search/(?P<query>[\S\s]+)/(?P<page>\d+)/$', 'search'),	
	
	(r'^replies/(?P<id>\w+)/$', 'replies'),
	(r'^about/$', 'about'),

	# Actions
	(r'^upload/$', 'upload'),
	(r'^upload_reply/(?P<in_reply_to>\w+)/$', 'upload'),
	(r'^voteup/(?P<id>\w+)/$', 'voteup'),
	(r'^report/(?P<id>\w+)/$', 'report'),
	(r'^validate/(?P<hash>\w+)/$', 'validate'),
)
