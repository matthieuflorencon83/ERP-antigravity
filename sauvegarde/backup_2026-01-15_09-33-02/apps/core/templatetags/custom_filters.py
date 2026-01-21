from django import template

register = template.Library()

@register.filter(name='multiply')
def multiply(value, arg):
    """
    Multiplie deux nombres dans un template.
    Usage: {{ quantite|multiply:prix }}
    """
    try:
        return float(value) * float(arg)
    except (ValueError, TypeError):
        return 0

@register.filter(name='to_float')
def to_float(value):
    """Convertit une string en float (utile pour les comparaisons)"""
    try:
        return float(value)
    except (ValueError, TypeError):
        return 0.0

@register.filter(name='get_item')
def get_item(dictionary, key):
    """Récupère une valeur de dictionnaire par sa clé"""
    return dictionary.get(key)

@register.filter(name='add_class')
def add_class(field, css_class):
    """Ajoute une classe CSS à un champ de formulaire"""
    if hasattr(field, 'as_widget'):
        return field.as_widget(attrs={"class": css_class})
    return field