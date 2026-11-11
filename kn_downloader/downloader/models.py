from django.db import models
from django.contrib.auth.models import User

class DownloadHistory(models.Model):
    FORMAT_CHOICES = [('mp4', 'MP4 Video'), ('mp3', 'MP3 Audio')]
    user = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True, related_name='downloads')
    url = models.URLField(max_length=2000)
    title = models.CharField(max_length=500, blank=True)
    thumbnail = models.URLField(max_length=2000, blank=True)
    platform = models.CharField(max_length=100, blank=True)
    format = models.CharField(max_length=10, choices=FORMAT_CHOICES, default='mp4')
    duration = models.CharField(max_length=50, blank=True)
    downloaded_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['-downloaded_at']

    def __str__(self):
        return f'{self.title or self.url} - {self.format}'
