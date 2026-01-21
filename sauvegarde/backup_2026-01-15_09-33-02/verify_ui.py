import requests
from bs4 import BeautifulSoup

def verify_page():
    url = "http://127.0.0.1:8000/documents/upload/"
    try:
        response = requests.get(url)
        print(f"Status Code: {response.status_code}")
        
        if response.status_code == 200:
            soup = BeautifulSoup(response.text, 'html.parser')
            
            # 1. Check Page Title Removal
            topbar = soup.find('header', class_='topbar')
            h5_title = topbar.find('h5') if topbar else None
            if h5_title:
                print("FAIL: Page Title (h5) still exists in topbar.")
            else:
                print("PASS: Page Title removed from topbar.")
                
            # 2. Check Theme Toggle Position (Should be in sidebar footer)
            # We assume sidebar footer is the div with border-top in .sidebar
            sidebar = soup.find('div', class_='sidebar')
            if sidebar:
                toggle = sidebar.find('button', id='bd-theme-toggle')
                if toggle:
                    print("PASS: Theme toggle found in sidebar.")
                else:
                    print("FAIL: Theme toggle NOT found in sidebar.")
            else:
                print("FAIL: Sidebar not found.")

            # 3. Check Active Link Highlighting
            # The link to /documents/upload/ should have 'active' class
            active_link = soup.find('a', href="/documents/upload/", class_='active')
            if active_link:
                print("PASS: IA Page link is marked 'active'.")
            else:
                # Debug: list all links
                print("FAIL: IA Page link is NOT active.")
                links = soup.find_all('a', class_='nav-link')
                for l in links:
                    print(f" - Link: {l.get('href')} Class: {l.get('class')}")
                    
            # 4. Check Styles/Structure
            main_content = soup.find('main', class_='main-content')
            if main_content:
                print("PASS: Main content container found.")
            else:
                print("FAIL: Main content container missing.")

        else:
            print("FAIL: Page returned non-200 status.")
            print(response.text[:500])

    except Exception as e:
        print(f"Error accessing page: {e}")

if __name__ == "__main__":
    verify_page()
