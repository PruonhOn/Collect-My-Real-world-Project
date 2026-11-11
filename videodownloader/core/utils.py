from django.shortcuts import render, redirect
from django.http import JsonResponse
from .utils import get_video_info, download_media

# ===== HOME PAGE =====
def home(request):
    return render(request, 'home.html')

# ===== FETCH VIDEO INFO =====
def fetch_info(request):
    if request.method == "POST":
        url = request.POST.get("url", "").strip()
        if not url:
            return JsonResponse({"error": "No URL provided"})

        info = get_video_info(url)
        return JsonResponse(info)
    return JsonResponse({"error": "Invalid request method"})

# ===== DOWNLOAD VIDEO / AUDIO =====
def download(request):
    if request.method == "POST":
        url = request.POST.get("url", "").strip()
        choice = request.POST.get("choice", "1")  # "1"=video, "2"=audio

        if not url:
            return JsonResponse({"error": "No URL provided"})

        result = download_media(url, choice)
        return JsonResponse(result)
    return JsonResponse({"error": "Invalid request method"})

# ===== USER / PROFILE (placeholder) =====
def signup_view(request):
    return render(request, 'signup.html')

def login_view(request):
    return render(request, 'login.html')

def logout_view(request):
    return redirect('home')

def profile_view(request):
    return render(request, 'profile.html')

def delete_history(request, pk):
    # Add delete logic if needed
    return redirect('home')