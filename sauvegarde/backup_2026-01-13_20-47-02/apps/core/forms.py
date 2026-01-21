from django import forms
from .models import Client, Fournisseur, Article, CoreContact
import uuid

class BootstrapMixin:
    """
    Mixin to automatically add 'form-control' class and placeholder to all fields.
    """
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        for field_name, field in self.fields.items():
            # Add Bootstrap class
            current_classes = field.widget.attrs.get('class', '')
            field.widget.attrs['class'] = f'{current_classes} form-control'.strip()
            
            # Add placeholder if not present
            if not field.widget.attrs.get('placeholder'):
                field.widget.attrs['placeholder'] = field.label or field_name.replace('_', ' ').title()

class ClientForm(BootstrapMixin, forms.ModelForm):
    # Champs virtuels pour le contact principal
    contact_nom = forms.CharField(max_length=100, required=False, label="Nom Contact")
    contact_prenom = forms.CharField(max_length=100, required=False, label="Prénom Contact")
    contact_email = forms.EmailField(required=False, label="Email Contact")
    contact_telephone = forms.CharField(max_length=20, required=False, label="Tél Contact")
    contact_fonction = forms.CharField(max_length=50, required=False, label="Fonction / Rôle")

    class Meta:
        model = Client
        fields = '__all__'

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # Pré-remplir les champs contact si existant
        if self.instance.pk:
            contact = self.instance.corecontact_set.first()
            if contact:
                self.fields['contact_nom'].initial = contact.nom
                self.fields['contact_prenom'].initial = contact.prenom
                self.fields['contact_email'].initial = contact.email
                self.fields['contact_telephone'].initial = contact.telephone
                self.fields['contact_fonction'].initial = contact.type_contact

    def save(self, commit=True):
        client = super().save(commit=commit)
        
        # Gestion du contact
        c_nom = self.cleaned_data.get('contact_nom')
        if c_nom: # Si au moins le nom est indiqué
            contact = client.corecontact_set.first()
            if not contact:
                contact = CoreContact(id=uuid.uuid4(), client=client)
            
            contact.nom = c_nom
            contact.prenom = self.cleaned_data.get('contact_prenom', '')
            contact.email = self.cleaned_data.get('contact_email', '')
            contact.telephone = self.cleaned_data.get('contact_telephone', '')
            contact.type_contact = self.cleaned_data.get('contact_fonction', 'Principal')
            contact.save()
            
        return client

class FournisseurForm(BootstrapMixin, forms.ModelForm):
    # Champs virtuels pour le contact principal
    contact_nom = forms.CharField(max_length=100, required=False, label="Nom Contact")
    contact_prenom = forms.CharField(max_length=100, required=False, label="Prénom Contact")
    contact_email = forms.EmailField(required=False, label="Email Contact")
    contact_telephone = forms.CharField(max_length=20, required=False, label="Tél Contact")
    contact_fonction = forms.CharField(max_length=50, required=False, label="Fonction / Rôle")

    class Meta:
        model = Fournisseur
        fields = '__all__'

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        if self.instance.pk:
            contact = self.instance.corecontact_set.first()
            if contact:
                self.fields['contact_nom'].initial = contact.nom
                self.fields['contact_prenom'].initial = contact.prenom
                self.fields['contact_email'].initial = contact.email
                self.fields['contact_telephone'].initial = contact.telephone
                self.fields['contact_fonction'].initial = contact.type_contact

    def save(self, commit=True):
        fournisseur = super().save(commit=commit)
        
        c_nom = self.cleaned_data.get('contact_nom')
        if c_nom:
            contact = fournisseur.corecontact_set.first()
            if not contact:
                contact = CoreContact(id=uuid.uuid4(), fournisseur=fournisseur)
            
            contact.nom = c_nom
            contact.prenom = self.cleaned_data.get('contact_prenom', '')
            contact.email = self.cleaned_data.get('contact_email', '')
            contact.telephone = self.cleaned_data.get('contact_telephone', '')
            contact.type_contact = self.cleaned_data.get('contact_fonction', 'Principal')
            contact.save()
            
        return fournisseur

class ArticleForm(BootstrapMixin, forms.ModelForm):
    class Meta:
        model = Article
        fields = '__all__'
