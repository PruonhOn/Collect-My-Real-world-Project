import yt_dlp
import re

SUPPORTED_PLATFORMS = [
    'youtube.com', 'youtu.be', 'vimeo.com', 'dailymotion.com',
    'twitter.com', 'x.com', 'instagram.com', 'facebook.com',
    'tiktok.com', 'twitch.tv', 'reddit.com', 'soundcloud.com',
    'bilibili.com', 'rumble.com', 'odysee.com',
]

def get_platform(url):
    for platform in SUPPORTED_PLATFORMS:
        if platform in url.lower():
            return platform.split('.')[0].capitalize()
    return 'Unknown'

def get_video_info(url):
    """Fetch video metadata without downloading."""
    ydl_opts = {
        'quiet': True,
        'no_warnings': True,
        'skip_download': True,
        'noplaylist': True,
    }
    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=False)
            duration_secs = info.get('duration', 0)
            if duration_secs:
                mins, secs = divmod(int(duration_secs), 60)
                hrs, mins = divmod(mins, 60)
                duration = f'{hrs:02d}:{mins:02d}:{secs:02d}' if hrs else f'{mins:02d}:{secs:02d}'
            else:
                duration = 'Unknown'

            formats = []
            for f in info.get('formats', []):
                if f.get('vcodec') != 'none' and f.get('acodec') != 'none':
                    fsize = f.get('filesize') or f.get('filesize_approx')
                    formats.append({
                        'format_id': f.get('format_id'),
                        'ext': f.get('ext'),
                        'height': f.get('height'),
                        'filesize': f'{round(fsize / 1024 / 1024, 1)} MB' if fsize else 'Unknown',
                    })

            return {
                'success': True,
                'title': info.get('title', 'Unknown Title'),
                'thumbnail': info.get('thumbnail', ''),
                'duration': duration,
                'uploader': info.get('uploader', 'Unknown'),
                'platform': get_platform(url),
                'view_count': info.get('view_count'),
                'like_count': info.get('like_count'),
                'description': (info.get('description') or '')[:300],
                'formats': formats[:8],
            }
    except yt_dlp.utils.DownloadError as e:
        err = str(e)
        if 'Unsupported URL' in err:
            msg = 'This URL is not supported. Please try YouTube, Vimeo, TikTok, Twitter, Instagram, etc.'
        elif 'Private video' in err or 'private' in err.lower():
            msg = 'This video is private and cannot be downloaded.'
        elif 'not available' in err.lower():
            msg = 'This video is not available in your region or has been removed.'
        elif 'age' in err.lower():
            msg = 'Age-restricted content cannot be downloaded without authentication.'
        else:
            msg = 'Could not fetch video info. Please check the URL and try again.'
        return {'success': False, 'error': msg}
    except Exception as e:
        return {'success': False, 'error': 'An unexpected error occurred. Please try again.'}


def download_video(url, fmt='mp4'):
    """Stream download and return file bytes."""
    import tempfile, os
    tmp = tempfile.mkdtemp()
    output_path = os.path.join(tmp, '%(title)s.%(ext)s')

    if fmt == 'mp3':
        ydl_opts = {
            'format': 'bestaudio/best',
            'outtmpl': output_path,
            'quiet': True,
            'no_warnings': True,
            'postprocessors': [{
                'key': 'FFmpegExtractAudio',
                'preferredcodec': 'mp3',
                'preferredquality': '192',
            }],
            'noplaylist': True,
        }
    else:
        ydl_opts = {
            'format': 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best',
            'outtmpl': output_path,
            'quiet': True,
            'no_warnings': True,
            'merge_output_format': 'mp4',
            'noplaylist': True,
        }

    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=True)
            title = info.get('title', 'download')
            # Find downloaded file
            for f in os.listdir(tmp):
                fpath = os.path.join(tmp, f)
                with open(fpath, 'rb') as fp:
                    data = fp.read()
                os.remove(fpath)
                safe_title = re.sub(r'[^\w\s-]', '', title)[:100].strip()
                ext = 'mp3' if fmt == 'mp3' else 'mp4'
                return {'success': True, 'data': data, 'filename': f'{safe_title}.{ext}', 'title': title}
        return {'success': False, 'error': 'File not found after download.'}
    except yt_dlp.utils.DownloadError as e:
        return {'success': False, 'error': f'Download failed: {str(e)[:200]}'}
    except Exception as e:
        return {'success': False, 'error': 'Download error. Please try again.'}
    finally:
        import shutil
        shutil.rmtree(tmp, ignore_errors=True)
