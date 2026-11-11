# K&N Downloader

A full-featured Django video downloader with Three.js animations, user profiles, and MP3/MP4 support.

## Features
- Download from 1000+ platforms (YouTube, TikTok, Instagram, Twitter, Vimeo, etc.)
- MP4 video & MP3 audio extraction
- User authentication (register, login, profile)
- Profile avatar upload and editing
- Download history tracking
- Three.js animated background on every page
- Production-ready for Render hosting

## Local Setup

```bash
# 1. Clone & enter
git clone <your-repo>
cd kn_downloader

# 2. Create virtual environment
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate

# 3. Install dependencies
pip install -r requirements.txt

# 4. Set environment variables
cp .env.example .env
# Edit .env and set your SECRET_KEY

# 5. Run migrations
python manage.py migrate

# 6. Create admin user (optional)
python manage.py createsuperuser

# 7. Run development server
python manage.py runserver
```

Visit http://localhost:8000

## Deploy to Render

1. Push code to GitHub
2. Go to https://render.com → New → Web Service
3. Connect your GitHub repo
4. Build Command: `./build.sh`
5. Start Command: `gunicorn kn_downloader.wsgi:application --bind 0.0.0.0:$PORT`
6. Add environment variables:
   - `SECRET_KEY` = (generate a long random string)
   - `DEBUG` = `False`
   - `ALLOWED_HOSTS` = `.onrender.com`
7. Deploy!

## Note on ffmpeg (for MP3)
MP3 extraction requires ffmpeg. On Render, add a `apt.txt` file:
```
ffmpeg
```

## Supported Sites (sample)
YouTube, Vimeo, TikTok, Twitter/X, Instagram, Facebook, Dailymotion,
Twitch, Reddit, SoundCloud, Bilibili, Rumble, Odysee, and 1000+ more via yt-dlp.
