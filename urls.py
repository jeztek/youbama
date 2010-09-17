from django.conf.urls.defaults import *
from youbama.settings import MEDIA_ROOT

urlpatterns = patterns('',
	(r'^admin/', include('django.contrib.admin.urls')),
	(r'^static/(?P<path>.*)$', 'django.views.static.serve', {'document_root'
 		: MEDIA_ROOT, 'show_indexes' : True}),
	(r'^', include('youbama.main.urls')),
)
