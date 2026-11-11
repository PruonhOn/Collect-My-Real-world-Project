import json
from django.shortcuts import render
from django.http import JsonResponse, HttpResponse
from django.views.decorators.http import require_POST
from django.views.decorators.csrf import csrf_protect
from django.contrib.auth.decorators import login_required
from .utils import get_video_info, download_video
from .models import DownloadHistory
from accounts.models import Profile


def home(request):
    # Get recent downloads (last 5) for the logged-in user
    recent = DownloadHistory.objects.filter(user=request.user).order_by('-created_at')[:5] if request.user.is_authenticated else []

    # Supported video/audio platforms
    platforms = [
        "YouTube","Vimeo","TikTok","Twitter/X","Instagram","Facebook",
        "Dailymotion","Twitch","Reddit","SoundCloud","Bilibili","Rumble","Odysee"
    ]

    return render(request, 'downloader/home.html', {
        'recent': recent,
        'platforms': platforms
    })


@require_POST
@csrf_protect
def fetch_info(request):
    try:
        data = json.loads(request.body)
        url = data.get('url', '').strip()
    except Exception:
        return JsonResponse({'success': False, 'error': 'Invalid request.'}, status=400)

    if not url:
        return JsonResponse({'success': False, 'error': 'Please provide a URL.'}, status=400)
    if not (url.startswith('http://') or url.startswith('https://')):
        return JsonResponse({'success': False, 'error': 'URL must start with http:// or https://'}, status=400)

    info = get_video_info(url)
    return JsonResponse(info)


@require_POST
@csrf_protect
def download(request):
    try:
        data = json.loads(request.body)
        url = data.get('url', '').strip()
        fmt = data.get('format', 'mp4').lower()
    except Exception:
        return JsonResponse({'success': False, 'error': 'Invalid request.'}, status=400)

    if not url:
        return JsonResponse({'success': False, 'error': 'URL is required.'}, status=400)
    if fmt not in ('mp3', 'mp4'):
        fmt = 'mp4'

    result = download_video(url, fmt)
    if not result.get('success', False):
        return JsonResponse({'success': False, 'error': result.get('error', 'Download failed.')}, status=400)

    # Log download to history
    try:
        info = get_video_info(url)
        hist = DownloadHistory.objects.create(
            user=request.user if request.user.is_authenticated else None,
            url=url,
            title=info.get('title', ''),
            thumbnail=info.get('thumbnail', ''),
            platform=info.get('platform', ''),
            format=fmt,
            duration=info.get('duration', ''),
        )

        # Update user profile download count
        if request.user.is_authenticated:
            profile, _ = Profile.objects.get_or_create(user=request.user)
            profile.total_downloads += 1
            profile.save()
    except Exception:
        pass  # Ignore logging errors

    content_type = 'audio/mpeg' if fmt == 'mp3' else 'video/mp4'
    response = HttpResponse(result['data'], content_type=content_type)
    response['Content-Disposition'] = f'attachment; filename="{result["filename"]}"'
    response['Content-Length'] = len(result['data'])
    return response


@login_required
def history(request):
    downloads = DownloadHistory.objects.filter(user=request.user).order_by('-created_at')
    return render(request, 'downloader/history.html', {'downloads': downloads})