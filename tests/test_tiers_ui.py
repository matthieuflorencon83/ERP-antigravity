from django.test import TestCase, Client as TestClient
from django.urls import reverse
from django.utils import timezone
from apps.tiers.models import Client
from django.contrib.auth.models import User

class ClientUIFlowTest(TestCase):
    def setUp(self):
        self.user = User.objects.create_user(username='testuser', password='password')
        self.client = TestClient()
        self.client.login(username='testuser', password='password')
        
        self.client_obj = Client.objects.create(
            id="CLI-TEST1",
            nom="Client Test UI",
            email_client="test@test.com",
            type_tiers="PROFESSIONNEL"
        )

    def test_client_list(self):
        """Test the client list view"""
        response = self.client.get(reverse('tiers:client_list'))
        self.assertEqual(response.status_code, 200)
        self.assertContains(response, "Client Test UI")
        self.assertContains(response, "CLI-TEST1")
