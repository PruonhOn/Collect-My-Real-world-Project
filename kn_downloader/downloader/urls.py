from django.urls import path
from . import views

urlpatterns = [
    path('', views.home, name='home'),
    path('fetch-info/', views.fetch_info, name='fetch_info'),
    path('download/', views.download, name='download'),
    path('history/', views.history, name='history'),
]
