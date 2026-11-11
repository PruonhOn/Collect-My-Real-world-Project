from django.contrib.auth import get_user_model
from django.test import TestCase
from django.urls import reverse


class DashboardAuthTests(TestCase):
    def setUp(self):
        self.user = get_user_model().objects.create_user(
            username='admin',
            password='AdminPass123!',
        )

    def test_dashboard_redirects_anonymous_users_to_login(self):
        response = self.client.get(reverse('dashboard'))

        self.assertRedirects(
            response,
            f"{reverse('login')}?next={reverse('dashboard')}",
            fetch_redirect_response=False,
        )

    def test_login_authenticates_user_and_redirects_to_dashboard(self):
        response = self.client.post(
            reverse('login'),
            {
                'username': 'admin',
                'password': 'AdminPass123!',
            },
        )

        self.assertRedirects(
            response,
            reverse('dashboard'),
            fetch_redirect_response=False,
        )

    def test_logout_redirects_back_to_login(self):
        self.client.force_login(self.user)

        response = self.client.get(reverse('logout'))

        self.assertRedirects(
            response,
            reverse('login'),
            fetch_redirect_response=False,
        )

        dashboard_response = self.client.get(reverse('dashboard'))
        self.assertRedirects(
            dashboard_response,
            f"{reverse('login')}?next={reverse('dashboard')}",
            fetch_redirect_response=False,
        )
