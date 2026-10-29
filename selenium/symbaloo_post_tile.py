#!/usr/bin/env python3
"""
Symbaloo — Add tile to Digital Marketing Service mix
Flow: Open mix → Double-click empty cell → tileSearchInput appears
      → Type URL → Enter → Tile appears → Done
"""
import os, sys, time, json
script_dir = os.path.dirname(os.path.abspath(__file__))
try:
    import pwd
    sys_user = pwd.getpwuid(os.getuid())[0]
except Exception:
    import getpass
    sys_user = getpass.getuser()
app_tmp_dir = os.path.join(script_dir, f'tmp_dir_{sys_user}')
try:
    os.makedirs(app_tmp_dir, exist_ok=True)
except Exception:
    pass
os.environ['HOME'] = app_tmp_dir
os.environ['WDM_LOG'] = '0'
os.environ['WDM_DIR'] = os.path.join(app_tmp_dir, '.wdm')
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.keys import Keys
from webdriver_manager.chrome import ChromeDriverManager

SCRIPT_DIR  = os.path.dirname(os.path.abspath(__file__))

# Args: email password keyword target_url
email      = sys.argv[1] if len(sys.argv) > 1 else "kanzariyapratik124@gmail.com"
password   = sys.argv[2] if len(sys.argv) > 2 else "@DISHA12@"
keyword    = sys.argv[3] if len(sys.argv) > 3 else "python training bangalore"
target_url = sys.argv[4] if len(sys.argv) > 4 else "https://learnmoretech.in/"
custom_mix_url = sys.argv[5] if len(sys.argv) > 5 else ""
ai_description = sys.argv[6] if len(sys.argv) > 6 else ""

import hashlib
email_hash = hashlib.md5(email.lower().encode('utf-8')).hexdigest()
try:
    import pwd
    sys_user = pwd.getpwuid(os.getuid())[0]
except Exception:
    import getpass
    sys_user = getpass.getuser()
PROFILE_DIR = os.path.join(SCRIPT_DIR, f'chrome_profile_symbaloo_{email_hash}_{sys_user}')

# Thoroughly clean lock files
if os.path.exists(PROFILE_DIR):
    for root, dirs, files in os.walk(PROFILE_DIR):
        for f in files:
            if f in ["SingletonLock", "SingletonCookie", "SingletonSocket", "LOCK", "lock", "DevToolsActivePort"]:
                p_file = os.path.join(root, f)
                try:
                    if os.path.islink(p_file): os.unlink(p_file)
                    else: os.remove(p_file)
                except Exception: pass

# Clear Cookies/Session storage to force fresh login each time (avoids stale redirect issues)
cookies_dir = os.path.join(PROFILE_DIR, 'Default')
for cookie_file in ['Cookies', 'Cookies-journal', 'Session Storage', 'Local Storage']:
    cp = os.path.join(cookies_dir, cookie_file)
    try:
        if os.path.isfile(cp):
            os.remove(cp)
        elif os.path.isdir(cp):
            import shutil
            shutil.rmtree(cp, ignore_errors=True)
    except Exception:
        pass

MIX_URL = "https://www.symbaloo.com/home/mix/13ePQXNM4g"

def log(msg):
    print(json.dumps({"log": msg}), flush=True)

def result(success, url='', error=''):
    print(json.dumps({"success": success, "url": url, "error": error}), flush=True)

def dismiss_overlays(driver):
    """Dismisses cookie consent, AdBlock dialogs, and Symbaloo Welcome onboarding tours.
    IMPORTANT: Does NOT remove login dialog/form elements."""
    try:
        driver.execute_script("""
            // 1. Remove CMP consent overlays & iframes
            document.querySelectorAll('iframe[id*="sp_message"], iframe[title*="Consent"], div[id*="sp_message"], #qc-cmp2-container, .qc-cmp2-container, .qc-cmp-cleanslate, #CybotCookiebotDialog, #onetrust-banner-sdk').forEach(el => el.remove());
            
            // 2. Remove AdBlock overlays - but only if they do NOT contain login inputs
            document.querySelectorAll('embedded-app-container[key="adBlockInformationDialog"], div[key="adBlockInformationDialog"]').forEach(el => {
                if (!el.querySelector('input[type="email"], input[type="password"]')) el.remove();
            });
            // aria-labelledby="dialogTitle" can match login form - skip it entirely
            
            // 3. Remove/dismiss Welcome tour popup and Skip Tour buttons
            Array.from(document.querySelectorAll('button, a')).forEach(b => {
                const t = (b.innerText || '').trim().toLowerCase();
                if (t === 'skip tour' || t.includes('skip tour')) {
                    try { b.click(); } catch(e){}
                }
            });
            // Only remove Welcome tour dialogs that do NOT contain login inputs
            document.querySelectorAll('[role="dialog"], .modal, [class*="Modal"], [class*="dialog"]').forEach(el => {
                const hasLoginInput = el.querySelector('input[type="email"], input[type="password"], #loginDialogEmailInput');
                if (!hasLoginInput) {
                    const text = (el.innerText || '');
                    if (text.includes('Welcome to Symbaloo') || text.includes('Skip Tour')) {
                        el.remove();
                    }
                }
            });
        """)
        # Native click for any visible Skip Tour buttons
        for sel in ["//button[contains(., 'Skip Tour')]", "//a[contains(., 'Skip Tour')]", "//button[contains(., 'Skip')]"]:
            try:
                b = driver.find_element(By.XPATH, sel)
                if b.is_displayed():
                    b.click()
                    log("Symbaloo: Clicked 'Skip Tour' natively")
            except: pass
    except Exception as e:
        log(f"Symbaloo: Overlay cleanup note: {e}")

def close_consent_modal(driver):
    return dismiss_overlays(driver)

def close_adblock_modal(driver):
    return dismiss_overlays(driver)

opts = Options()
opts.add_argument('--headless=new')
opts.add_argument('--no-sandbox')
opts.add_argument('--disable-dev-shm-usage')
opts.add_argument('--disable-gpu')
opts.add_argument('--disable-software-rasterizer')
opts.add_argument('--disable-blink-features=AutomationControlled')
opts.add_argument('--remote-debugging-port=0')
opts.add_argument('--disable-extensions')
opts.add_argument('--disable-infobars')
opts.add_argument('--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36')
opts.add_experimental_option('excludeSwitches', ['enable-automation'])
opts.add_experimental_option('useAutomationExtension', False)
try:
    opts.set_capability('goog:loggingPrefs', {'browser': 'ALL'})
except: pass
# Do NOT use persistent profile - stale sessions cause /login redirect issues
# opts.add_argument(f'--user-data-dir={PROFILE_DIR}')
# opts.add_argument('--profile-directory=Default')
opts.add_argument('--window-size=1400,900')
opts.page_load_strategy = 'eager'

log("Symbaloo: Initializing Chrome WebDriver...")
driver = None
try:
    driver = webdriver.Chrome(options=opts)
    log("Symbaloo: Chrome started via Selenium Manager.")
except Exception as e1:
    log(f"Symbaloo: Selenium Manager fallback ({e1}), trying ChromeDriverManager...")
    try:
        driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=opts)
        log("Symbaloo: Chrome started via ChromeDriverManager.")
    except Exception as e2:
        result(False, error=f"Symbaloo: Chrome launch failed ({e2})")
        sys.exit(1)

driver.set_page_load_timeout(40)
driver.set_script_timeout(30)
driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")

try:
    log("Symbaloo: Navigating to login page...")
    try:
        driver.get("https://www.symbaloo.com/login")
    except Exception as te:
        log(f"Symbaloo: Login get note ({te}), continuing...")
    time.sleep(5)

    dismiss_overlays(driver)

    # Always try to find login form (regardless of URL - Symbaloo may redirect)
    already_logged_in = any(x in driver.current_url.lower() for x in ["/home/mix", "/mix/"])
    if already_logged_in:
        log(f"Symbaloo: Already logged in! URL = {driver.current_url}")
    else:
        log("Symbaloo: Waiting for login form fields...")
        email_input = None
        for attempt in range(25):
            dismiss_overlays(driver)
            # Try to force mount Elm app if DOMContentLoaded fired prematurely
            try:
                driver.execute_script("""
                    try {
                        var ph = document.getElementById('elmPagePlaceholder');
                        if (ph && ph.children.length === 0) {
                            if (window.Elm && window.Elm.Pages && window.Elm.Pages.Login && window.Elm.Pages.Login.Page) {
                                window.Elm.Pages.Login.Page.init({
                                    node: ph,
                                    flags: { url: document.location.href, email: null, error: null }
                                });
                            } else {
                                document.dispatchEvent(new Event('DOMContentLoaded'));
                                document.dispatchEvent(new Event('readystatechange'));
                            }
                        }
                        // Also try dispatching load event to help deferred scripts
                        window.dispatchEvent(new Event('load'));
                    } catch(e) {}
                """)
            except: pass

            for sel in [
                "#loginDialogEmailInput",
                "input[id*='emailInput']",
                "input[type='email']",
                "input[name='email']",
                "#email",
                "input[placeholder*='email' i]",
                "input[placeholder*='E-mail' i]",
                "input[type='text']"
            ]:
                try:
                    inp = driver.find_element(By.CSS_SELECTOR, sel)
                    if inp.is_displayed():
                        email_input = inp
                        log(f"Symbaloo: Found email input [{sel}] on attempt {attempt+1}")
                        break
                except: pass
            if email_input:
                break
            time.sleep(1)

        # Fallback: if /login did not render inputs, try navigating to /login again with page_load_strategy=eager
        if not email_input:
            log("Symbaloo: Retrying /login directly one more time...")
            try:
                driver.get("https://www.symbaloo.com/login")
                time.sleep(5)
                dismiss_overlays(driver)
                for sel in ["#loginDialogEmailInput", "input[id*='emailInput']", "input[type='email']", "input[name='email']", "#email", "input[placeholder*='email' i]", "input[type='text']"]:
                    try:
                        inp = driver.find_element(By.CSS_SELECTOR, sel)
                        if inp.is_displayed():
                            email_input = inp
                            log(f"Symbaloo: Found email input on 2nd /login attempt [{sel}]")
                            break
                    except: pass
            except Exception as e_retry:
                log(f"Symbaloo: Retry /login note: {e_retry}")

        if not email_input:
            driver.save_screenshot(os.path.join(SCRIPT_DIR, 'symbaloo_error.png'))
            try:
                with open(os.path.join(SCRIPT_DIR, 'symbaloo_page_dump.html'), 'w', encoding='utf-8') as f:
                    f.write(driver.page_source)
                logs = driver.get_log('browser')
                with open(os.path.join(SCRIPT_DIR, 'symbaloo_console.json'), 'w', encoding='utf-8') as f:
                    f.write(json.dumps(logs))
            except: pass
            result(False, error="Symbaloo: Email input not found on login page (Page load or consent issue)")
            sys.exit(0)

        # Type email
        driver.execute_script("arguments[0].scrollIntoView({block:'center'});", email_input)
        email_input.click()
        email_input.clear()
        email_input.send_keys(email)
        log("Symbaloo: Email entered")
        time.sleep(0.5)

        # Type password
        pass_input = None
        for sel in ["#loginDialogPasswordInput", "input[id*='passwordInput']", "input[type='password']", "input[name='password']", "#password"]:
            try:
                inp = driver.find_element(By.CSS_SELECTOR, sel)
                if inp.is_displayed():
                    pass_input = inp
                    break
            except: pass

        if not pass_input:
            driver.save_screenshot(os.path.join(SCRIPT_DIR, 'symbaloo_error.png'))
            result(False, error="Symbaloo: Password input not found on login page")
            sys.exit(0)

        pass_input.click()
        pass_input.clear()
        pass_input.send_keys(password)
        log("Symbaloo: Password entered")
        time.sleep(0.5)

        # Submit form
        submitted = False
        for sel in ["button[type='submit']", "button.login-button", "#login-button", "//button[contains(., 'Log in') or contains(., 'Sign in') or contains(., 'Inloggen')]"]:
            try:
                btn = driver.find_element(By.XPATH if sel.startswith('/') else By.CSS_SELECTOR, sel)
                if btn.is_displayed():
                    driver.execute_script("arguments[0].click();", btn)
                    submitted = True
                    log(f"Symbaloo: Clicked submit button via {sel}")
                    break
            except: continue

        if not submitted:
            pass_input.send_keys(Keys.ENTER)
            log("Symbaloo: Pressed Enter to submit login form")

        # Wait up to 15 seconds for login redirect
        log("Symbaloo: Waiting for login session redirect...")
        logged_in = False
        for _ in range(15):
            time.sleep(1)
            dismiss_overlays(driver)
            curr = driver.current_url.lower()
            if "login" not in curr:
                logged_in = True
                log(f"Symbaloo: Successfully logged in! Redirected to: {driver.current_url}")
                break
            # Check for bad credentials error
            try:
                b_text = driver.find_element(By.TAG_NAME, 'body').text.lower()
                if "invalid email or password" in b_text or "incorrect password" in b_text or "invalid credentials" in b_text:
                    driver.save_screenshot(os.path.join(SCRIPT_DIR, 'symbaloo_error.png'))
                    result(False, error="Symbaloo: Invalid email or password.")
                    sys.exit(0)
            except: pass

        if not logged_in:
            log("Symbaloo: Still on login URL after 15s, attempting to continue...")

    # Handle Webmix navigation
    dismiss_overlays(driver)
    if custom_mix_url and "symbaloo.com" in custom_mix_url:
        log(f"Symbaloo: Navigating to custom target mix = {custom_mix_url}")
        try:
            driver.get(custom_mix_url)
        except Exception as te:
            log(f"Symbaloo: Mix page get note ({te}), continuing...")
        time.sleep(5)
        dismiss_overlays(driver)
    else:
        # If user landed on home/mix or root, check if we need to load home webmix
        curr = driver.current_url.lower()
        if "symbaloo.com/home" not in curr and "symbaloo.com/mix" not in curr:
            log("Symbaloo: Navigating to default user home mix...")
            try:
                driver.get("https://www.symbaloo.com/home")
            except: pass
            time.sleep(5)
            dismiss_overlays(driver)

    log(f"Symbaloo: Webmix URL = {driver.current_url}")
    dismiss_overlays(driver)

    # Check if webmix is read-only public mix and needs to be added to user account
    try:
        for btn in driver.find_elements(By.TAG_NAME, 'button'):
            btxt = btn.text.strip().lower()
            if 'add this webmix' in btxt or 'add webmix' in btxt or 'copy webmix' in btxt:
                driver.execute_script("arguments[0].click();", btn)
                log("Symbaloo: Clicked 'Add this Webmix' to make it editable")
                time.sleep(3)
                dismiss_overlays(driver)
                break
    except Exception as ce:
        log(f"Symbaloo: Copy webmix note: {ce}")

    # Check if we need to enter Edit mode
    try:
        edit_clicked = False
        for btn in driver.find_elements(By.TAG_NAME, 'button'):
            txt = btn.text.strip()
            if 'Edit Webmix' in txt or 'Edit webmix' in txt:
                driver.execute_script("arguments[0].click();", btn)
                log("Symbaloo: Clicked 'Edit Webmix' to enter edit mode")
                time.sleep(3)
                edit_clicked = True
                dismiss_overlays(driver)
                break
        if not edit_clicked:
            for el in driver.find_elements(By.XPATH, "//*[contains(text(), 'Edit Webmix') or contains(text(), 'Edit webmix')]"):
                try:
                    if el.is_displayed():
                        driver.execute_script("arguments[0].click();", el)
                        log("Symbaloo: Clicked 'Edit Webmix' element to enter edit mode")
                        time.sleep(3)
                        dismiss_overlays(driver)
                        break
                except: continue
    except Exception as e:
        log(f"Symbaloo: Edit mode click error: {e}")

    dismiss_overlays(driver)

    # Find empty cells
    cells = driver.find_elements(By.CSS_SELECTOR, "[id^='gridEmptyCell'], div[class*='emptyCell'], div[class*='empty-cell'], [data-test-id*='empty-cell']")
    log(f"Symbaloo: Empty cells = {len(cells)}")

    if not cells:
        driver.save_screenshot(os.path.join(SCRIPT_DIR, 'symbaloo_error.png'))
        result(False, error="Symbaloo: No empty cells in this mix (Webmix is full)")
        sys.exit(0)

    # Try up to 6 empty cells to open the tile addition sidebar
    tile_input = None
    max_cells_to_try = min(len(cells), 6)

    for idx in range(max_cells_to_try):
        cell = cells[idx]
        log(f"Symbaloo: Trying empty cell #{idx+1}...")
        dismiss_overlays(driver)

        try:
            driver.execute_script("arguments[0].scrollIntoView({block:'center'});", cell)
            time.sleep(0.3)
        except: continue

        # 1. ActionChains native double click
        try:
            ActionChains(driver).move_to_element(cell).double_click().perform()
            time.sleep(1)
        except: pass

        # 2. JS double click event
        try:
            driver.execute_script("""
                var target = arguments[0];
                var clickEvent = new MouseEvent('dblclick', { bubbles: true, cancelable: true, view: window });
                target.dispatchEvent(clickEvent);
            """, cell)
            time.sleep(1)
        except: pass

        # 3. Single click via JS
        try:
            driver.execute_script("arguments[0].click();", cell)
            time.sleep(1)
        except: pass

        dismiss_overlays(driver)

        # Check if tileSearchInput is visible
        for sel in [
            "#tileSearchInput",
            "input[placeholder*='URL' i]",
            "input[placeholder*='url' i]",
            "input[placeholder*='search query' i]",
            "input[placeholder*='Enter a URL' i]",
            "input[placeholder*='search' i]",
            "input[type='url']",
            "input[name='url']",
            ".sidebar input[type='text']"
        ]:
            try:
                els = driver.find_elements(By.CSS_SELECTOR, sel)
                for el in els:
                    if el.is_displayed():
                        tile_input = el
                        log(f"Symbaloo: Found tile input [{sel}] on cell #{idx+1}!")
                        break
                if tile_input: break
            except: continue

        if tile_input:
            break

    if not tile_input:
        driver.save_screenshot(os.path.join(SCRIPT_DIR, 'symbaloo_error.png'))
        result(False, error="Symbaloo: tileSearchInput not found after trying multiple empty cells")
        sys.exit(0)

    # Type target URL
    try:
        tile_input.click()
    except Exception as e:
        log(f"Symbaloo: Standard click on input failed ({e}), trying JS click")
        try:
            driver.execute_script("arguments[0].click();", tile_input)
        except: pass
    tile_input.clear()
    time.sleep(0.3)
    tile_input.send_keys(target_url)
    log(f"Symbaloo: URL typed = {target_url}")
    time.sleep(1)

    # Press Enter to search/add — re-find element to avoid stale
    try:
        tile_input.send_keys(Keys.RETURN)
        log("Symbaloo: Enter pressed on input")
    except:
        # Element went stale — find again and press Enter
        try:
            el2 = driver.find_element(By.ID, 'tileSearchInput')
            el2.send_keys(Keys.RETURN)
            log("Symbaloo: Enter pressed on re-found input")
        except:
            # Just press Enter on body
            driver.find_element(By.TAG_NAME,'body').send_keys(Keys.RETURN)
            log("Symbaloo: Enter pressed on body")
    time.sleep(6)

    driver.save_screenshot(os.path.join(SCRIPT_DIR, 'symbaloo_tile_added.png'))

    # After Enter, wait then find fresh buttons
    time.sleep(6)
    driver.save_screenshot(os.path.join(SCRIPT_DIR, 'symbaloo_tile_added.png'))

    # Fresh find — page may have re-rendered
    def fresh_buttons():
        try:
            return driver.find_elements(By.TAG_NAME, 'button')
        except:
            return []

    btns = fresh_buttons()
    btn_texts = []
    for b in btns:
        try:
            if b.is_displayed() and b.text.strip():
                btn_texts.append(b.text.strip())
        except: continue
    log(f"Symbaloo: Buttons after search = {btn_texts[:15]}")

    # Click result items first
    added = False
    for sel in [
        "[class*='result']", "[class*='searchResult']",
        "[class*='suggestion']", "[class*='tileResult']",
    ]:
        try:
            els = driver.find_elements(By.CSS_SELECTOR, sel)
            for el in els:
                try:
                    if el.is_displayed():
                        driver.execute_script("arguments[0].click();", el)
                        log(f"Symbaloo: Clicked result [{sel}]")
                        time.sleep(3); added = True; break
                except: continue
            if added: break
        except: continue

    if not added:
        log("Symbaloo: No result button — tile auto-added on Enter")

    # Now look for "Edit Tile" — fill URL, name, description
    time.sleep(2)
    for btn in fresh_buttons():
        try:
            txt = btn.text.strip()
            if txt == 'Edit Tile' and btn.is_displayed():
                driver.execute_script("arguments[0].click();", btn)
                log("Symbaloo: Edit Tile clicked")
                time.sleep(4)

                # Fill Tile URL (name='url')
                for inp in driver.find_elements(By.CSS_SELECTOR, "input[name='url']"):
                    try:
                        if inp.is_displayed():
                            inp.click(); inp.clear(); time.sleep(0.2)
                            inp.send_keys(target_url)
                            log("Symbaloo: URL = " + target_url); break
                    except: continue

                # Fill Tile name (name='name')
                tile_title = "Best " + keyword.title() + " Training"
                for inp in driver.find_elements(By.CSS_SELECTOR, "input[name='name']"):
                    try:
                        if inp.is_displayed():
                            inp.click(); inp.clear(); time.sleep(0.2)
                            inp.send_keys(tile_title)
                            log("Symbaloo: Name = " + tile_title); break
                    except: continue

                # Fill Tile description (textarea)
                if ai_description:
                    tile_desc = ai_description
                else:
                    tile_desc = ("Best " + keyword + " training at Learnmore Technologies. "
                                 "Expert trainers, live projects, placement support. "
                                 "Enroll: " + target_url)
                for ta in driver.find_elements(By.TAG_NAME, 'textarea'):
                    try:
                        if ta.is_displayed():
                            ta.click(); ta.clear(); time.sleep(0.2)
                            ta.send_keys(tile_desc[:300])
                            log("Symbaloo: Description filled"); break
                    except: continue
                break
        except: continue

    # Click "Finish editing Webmix"
    time.sleep(2)
    for btn in fresh_buttons():
        try:
            txt = btn.text.strip()
            if 'Finish' in txt or 'finish' in txt.lower():
                driver.execute_script("arguments[0].click();", btn)
                log(f"Symbaloo: Clicked '{txt}'")
                time.sleep(4); break
        except: continue

    driver.save_screenshot(os.path.join(SCRIPT_DIR, 'symbaloo_tile_final.png'))
    log(f"Symbaloo: Final URL = {driver.current_url}")
    result(True, url=driver.current_url)

except Exception as e:
    result(False, error=str(e))
finally:
    try: driver.quit()
    except: pass
