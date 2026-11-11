from django.contrib import messages
from django.contrib.auth import authenticate, login, logout
from django.contrib.auth.decorators import login_required
from django.shortcuts import redirect, render
from django.utils.http import url_has_allowed_host_and_scheme


def _get_safe_next_url(request):
    next_url = request.POST.get('next') or request.GET.get('next')
    if next_url and url_has_allowed_host_and_scheme(
        next_url,
        allowed_hosts={request.get_host()},
        require_https=request.is_secure(),
    ):
        return next_url
    return None


def login_view(request):
    if request.user.is_authenticated:
        return redirect('dashboard')

    next_url = _get_safe_next_url(request)

    if request.method == 'POST':
        username = request.POST.get('username', '').strip()
        password = request.POST.get('password', '')
        user = authenticate(request, username=username, password=password)

        if user is not None:
            login(request, user)
            return redirect(next_url or 'dashboard')

        messages.error(request, 'Invalid username or password.')

    return render(
        request,
        'dashboard/login.html',
        {
            'next': next_url or '',
        },
    )


def logout_view(request):
    logout(request)
    return redirect('login')


@login_required
def dashboard(request):
    return render(request, 'dashboard/index.html')
