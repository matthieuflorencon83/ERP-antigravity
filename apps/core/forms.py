from django import forms
from apps.tiers.models import Client, Fournisseur, CoreContact
from apps.catalogue.models import Article
from apps.achats.models import Commande
from .models import CoreParametre
import uuid

class BootstrapMixin:
    """
    Mixin to automatically add 'form-control' class and placeholder to all fields.
    """
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        for field_name, field in self.fields.items():
            # Skip Checkbox/Radio from getting form-control
            if isinstance(field.widget, (forms.CheckboxInput, forms.RadioSelect, forms.CheckboxSelectMultiple)):
                continue

            # Add Bootstrap class
            current_classes = field.widget.attrs.get('class', '')
            field.widget.attrs['class'] = f'{current_classes} form-control'.strip()
            
            # Add placeholder if not present
            if not field.widget.attrs.get('placeholder'):
                field.widget.attrs['placeholder'] = field.label or field_name.replace('_', ' ').title()

class DataCleaningMixin:
    """
    Nettoyeur Universel (Data Cleaning)
    Règles Métier strictes :
    - Nom : UPPERCASE
    - Prénom : Title Case
    - Téléphone : 10 chiffres (suppression espaces/symboles)
    - Email : .fr ou .com obligatoires, lowercase
    """

    # --- LOGIQUE DE NETTOYAGE ---

    def _clean_upper(self, value):
        return value.upper() if value else value

    def _clean_title(self, value):
        return value.title() if value else value

    def _clean_phone(self, value):
        if not value:
            return value
        
        # Nettoyage : suppression espaces, points, tirets, etc.
        clean_val = value.replace(' ', '').replace('.', '').replace('-', '').replace('_', '').replace('/', '')
        
        # Validation
        if not clean_val.isdigit():
            # Ne rien lever ici pour laisser Django gérer les erreurs de format si besoin, 
            # ou lever ValidationError si on veut être strict.
            # On va être permissif sur le format et laisser le clean spécifique gérer si besoin.
            # Mais les règles disent "10 chiffres".
             raise forms.ValidationError("Le téléphone ne doit contenir que des chiffres.")
        
        if len(clean_val) != 10:
             raise forms.ValidationError(f"Le numéro doit comporter exactement 10 chiffres (actuel: {len(clean_val)}).")
             
        return clean_val

    def _clean_email(self, value):
        if not value:
            return value
        
        value = value.lower().strip()
        if not (value.endswith('.fr') or value.endswith('.com')):
             # Relaxing strict rule for now as it blocks legitimate emails? 
             # Re-enforcing as per requested rules.
            raise forms.ValidationError("Seuls les emails en '.fr' ou '.com' sont acceptés.")
        return value

    # --- HOOKS STANDARDS (Si les champs s'appellent nom, prenom, etc.) ---

    def clean_nom(self):
        return self._clean_upper(self.cleaned_data.get('nom'))

    def clean_prenom(self):
        return self._clean_title(self.cleaned_data.get('prenom'))

    def clean_telephone(self):
         return self._clean_phone(self.cleaned_data.get('telephone'))

    def clean_email(self):
         return self._clean_email(self.cleaned_data.get('email'))


class ClientForm(BootstrapMixin, DataCleaningMixin, forms.ModelForm):
    # Champs virtuels pour le contact principal
    contact_nom = forms.CharField(max_length=100, required=False, label="Nom Contact")
    contact_prenom = forms.CharField(max_length=100, required=False, label="Prénom Contact")
    contact_email = forms.EmailField(required=False, label="Email Contact")
    contact_telephone = forms.CharField(max_length=20, required=False, label="Tél Contact")
    contact_fonction = forms.CharField(max_length=50, required=False, label="Fonction / Rôle")
    
    # Explicit Type Tiers for Radio Widget
    type_tiers = forms.ChoiceField(
        choices=[('PARTICULIER', 'Particulier'), ('PROFESSIONNEL', 'Professionnel')],
        widget=forms.RadioSelect,
        label="Type de Tiers",
        initial='PARTICULIER'
    )

    class Meta:
        model = Client
        fields = '__all__'
        # Widgets defined in field directly now
        widgets = {
            'type_tiers': forms.RadioSelect(choices=[
                ('PARTICULIER', 'Particulier'),
                ('PROFESSIONNEL', 'Professionnel')
            ])
        }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # Pré-remplir les champs contact
        if self.instance.pk:
            contact = self.instance.corecontact_set.first()
            if contact:
                self.fields['contact_nom'].initial = contact.nom
                self.fields['contact_prenom'].initial = contact.prenom
                self.fields['contact_email'].initial = contact.email
                self.fields['contact_telephone'].initial = contact.telephone
                self.fields['contact_fonction'].initial = contact.type_contact

    # --- MAPPING NETTOYAGE CLIENT ---

    # 'clean_nom' et 'clean_prenom' gérés automatiquement par DataCleaningMixin car noms correspondent (nom, prenom)
    
    def clean_telephone_client(self):
        return self._clean_phone(self.cleaned_data.get('telephone_client'))

    def clean_email_client(self):
        return self._clean_email(self.cleaned_data.get('email_client'))

    # Nettoyage Contact
    def clean_contact_nom(self):
        return self._clean_upper(self.cleaned_data.get('contact_nom'))

    def clean_contact_prenom(self):
        return self._clean_title(self.cleaned_data.get('contact_prenom'))
    
    def clean_contact_telephone(self):
         return self._clean_phone(self.cleaned_data.get('contact_telephone'))
         
    def clean_contact_email(self):
         return self._clean_email(self.cleaned_data.get('contact_email'))

    def save(self, commit=True):
        client = super().save(commit=commit)
        
        # Gestion du contact
        c_nom = self.cleaned_data.get('contact_nom')
        if c_nom: 
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

class FournisseurForm(BootstrapMixin, DataCleaningMixin, forms.ModelForm):
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

    # --- MAPPING NETTOYAGE FOURNISSEUR ---

    def clean_nom_fournisseur(self):
        return self._clean_upper(self.cleaned_data.get('nom_fournisseur'))

    def clean_contact_nom(self):
        return self._clean_upper(self.cleaned_data.get('contact_nom'))

    def clean_contact_prenom(self):
        return self._clean_title(self.cleaned_data.get('contact_prenom'))
    
    def clean_contact_telephone(self):
         return self._clean_phone(self.cleaned_data.get('contact_telephone'))
         
    def clean_contact_email(self):
         return self._clean_email(self.cleaned_data.get('contact_email'))

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

class CommandeForm(BootstrapMixin, forms.ModelForm):
    class Meta:
        model = Commande
        fields = ['numero_bdc', 'date_commande', 'statut', 'affaire']
        widgets = {
            'date_commande': forms.DateInput(attrs={'type': 'date'}),
        }

class ParametreForm(BootstrapMixin, forms.ModelForm):
    class Meta:
        model = CoreParametre
        fields = ['nom_societe', 'chemin_stockage_local', 'gemini_api_key'] 
        widgets = {
            'gemini_api_key': forms.PasswordInput(render_value=True),
        }
