from django.db import models
from django.contrib.auth.models import User


# TODO: Create index on email
class Contact(models.Model):
	email = models.CharField(max_length=200)
	validated = models.BooleanField()

	def __unicode__(self):
		return self.email
		
	class Admin:
		pass


# TODO: Create index on in_reply_to
class Video(models.Model):
	user = models.ForeignKey(User)
	contact = models.ForeignKey(Contact)
	
	# Type 1: upload, 2: youtube url
	type = models.IntegerField()
	add_datetime = models.DateTimeField()

	title = models.CharField(max_length=200)
	short_description = models.TextField(null=True)
	description = models.TextField(null=True)
	
	duration_seconds = models.IntegerField(null=True)
	duration_string = models.TextField(null=True)

	viewcount = models.IntegerField()
	votecount = models.IntegerField()
	reportcount = models.IntegerField()

	in_reply_to = models.IntegerField(null=True) 
	replycount = models.IntegerField()

	validation_hash = models.CharField(max_length=100)
	visible = models.BooleanField()	
	
	youtube_id = models.CharField(max_length=100)
	
	def __unicode__(self):
		return str(self.id) + "_" + self.title

	class Admin:
		pass


class Upload(models.Model):
	video = models.ForeignKey(Video)
	file = models.FileField(upload_to='videos/')

	uploaded = models.BooleanField()
	processed = models.BooleanField()
	validated = models.BooleanField()

	def __unicode__(self):
		return str(self.id) + "_" + self.file
		
	class Admin:
		pass

