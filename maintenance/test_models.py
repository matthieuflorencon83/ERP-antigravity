import google.generativeai as genai

# Clé fournie par l'utilisateur
api_key = "AIzaSyAYR9m0CwI6at_2zS_sGN3fdOBuH64GDo8"

print(f"DEBUG: Utilisation de la clé fournie (commençant par {api_key[:4]}...)")
genai.configure(api_key=api_key)

try:
    print("--- MODÈLES DISPONIBLES ---")
    models = genai.list_models()
    found = False
    for m in models:
        if 'generateContent' in m.supported_generation_methods:
            print(f"Nom: {m.name} (Affichage: {m.display_name})")
            found = True
    if not found:
        print("Aucun modèle 'generateContent' trouvé.")
except Exception as e:
    print(f"ERREUR DE CONNEXION : {e}")
