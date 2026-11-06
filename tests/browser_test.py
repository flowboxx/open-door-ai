"""flowboxx Door browser test: admin save, address list, check button, block in the editor, block on the front end.
Run via tests/run_browser_test.sh (needs the browser compose from this folder). Python 3 + playwright, headless Chromium only."""
import os, sys, re
from playwright.sync_api import sync_playwright

BASE = "https://localhost:8443"
FAIL = []
SHOT = os.environ.get('SHOT_DIR', '/tmp')


def ok(msg):
    print("OK   " + msg)


def bad(msg):
    print("FAIL " + msg)
    FAIL.append(msg)


def check(cond, msg):
    ok(msg) if cond else bad(msg)


with sync_playwright() as p:
    b = p.chromium.launch()
    ctx = b.new_context(ignore_https_errors=True, viewport={"width": 1400, "height": 900})
    page = ctx.new_page()
    errors = []
    page.on("pageerror", lambda e: errors.append("pageerror: " + str(e)))
    page.on("console", lambda m: errors.append("console: " + m.text) if m.type == "error" and "favicon" not in m.text and "Failed to load resource" not in m.text else None)

    # 1. login
    page.goto(BASE + "/wp-login.php")
    page.fill("#user_login", "a")
    page.fill("#user_pass", "a1b2c3d4e5")
    page.click("#wp-submit")
    page.wait_for_url(re.compile(r"/wp-admin/"))
    ok("Login im Browser")

    # 2. settings page: fill, save
    page.goto(BASE + "/wp-admin/options-general.php?page=open-door-ai")
    check(page.locator("#open-door-ai-company").count() == 1, "Einrichtungs-Seite sichtbar")
    check(page.locator("a[href*='.well-known']").count() == 0, "vor dem Speichern keine Adressliste")
    page.fill("#open-door-ai-company", "Meier Dach GmbH")
    page.fill("#open-door-ai-services", "Dachsanierung\nFlachdach abdichten")
    page.fill("#open-door-ai-mail", "info@example.com")
    page.click("input#submit")
    page.wait_for_load_state("networkidle")
    check(page.locator("#setting-error-settings_updated, .notice-success").count() >= 1, "Speichern: Erfolgsmeldung")
    check(page.input_value("#open-door-ai-company") == "Meier Dach GmbH", "Wert nach Speichern wieder im Feld")
    links = page.locator("ul li a[href*='.well-known'], ul li a[href*='llms.txt']").count()
    check(links == 4, "Adressliste mit 4 Adressen (gefunden: %d)" % links)
    check(page.locator("input[name*='footer']").is_checked() is False, "Footer-Link standardmaessig AUS")
    page.add_style_tag(content=".update-nag,.notice-warning{display:none!important}")
    page.set_viewport_size({"width": 1400, "height": 1000})
    page.screenshot(path=SHOT + "/screenshot-1.png")
    page.set_viewport_size({"width": 1400, "height": 900})

    # 3. public addresses via browser session (https)
    r = page.request.get(BASE + "/.well-known/agent-card.json", ignore_https_errors=True)
    check(r.status == 200 and "Meier Dach GmbH" in r.text(), "agent-card.json liefert Firmennamen")

    # 4. check button
    page.click("input#open_door_check")
    page.wait_for_load_state("networkidle")
    txt = page.inner_text("div.wrap")
    check(("OK: " in txt) or ("Not reachable" in txt), "Check-Knopf zeigt je Adresse eine Zeile (im Testnetz 'Not reachable' erwartet)")

    # 5. block editor
    page.goto(BASE + "/wp-admin/post-new.php?post_type=page")
    page.wait_for_function("window.wp && wp.data && wp.data.select('core/editor') && wp.data.select('core/block-editor')", timeout=60000)
    page.evaluate("wp.data.dispatch('core/preferences').set('core/edit-post','welcomeGuide',false); wp.data.dispatch('core/preferences').set('core/edit-post','fullscreenMode',false)")
    page.reload()
    page.wait_for_function("window.wp && wp.data && wp.data.select('core/block-editor')", timeout=60000)
    check(page.evaluate("!!wp.blocks.getBlockType('open-door-ai/ask')"), "Block im Editor registriert (editor.js geladen)")
    page.fill("textarea.editor-post-title__input, h1.editor-post-title__input, [aria-label='Add title']", "Info") if page.locator("textarea.editor-post-title__input").count() else None
    # inserter via UI
    page.click("button[aria-label='Block Inserter'], button[aria-label='Toggle block inserter']")
    page.fill("input[placeholder*='Search']", "Ask an AI")
    item = page.locator("button[role='option']:has-text('Ask an AI about us'), .block-editor-block-types-list__item:has-text('Ask an AI about us')").first
    item.wait_for(timeout=15000)
    ok("Block im Inserter auffindbar")
    item.click()
    page.wait_for_timeout(2500)
    n = page.evaluate("wp.data.select('core/block-editor').getBlocks().filter(b=>b.name==='open-door-ai/ask').length")
    check(n == 1, "Block per Inserter eingefuegt")
    frame = page.frame_locator("iframe[name='editor-canvas']") if page.locator("iframe[name='editor-canvas']").count() else page
    frame.locator(".open-door-ai-ask, .wp-block-open-door-ai-ask").first.wait_for(timeout=20000)
    check("Ask an AI about us" in frame.locator("body").inner_text(), "Block zeigt serverseitig gerenderte Vorschau im Editor")
    check("Meier Dach GmbH" in frame.locator("body").inner_text(), "Vorschau nennt den gespeicherten Firmennamen")
    # publish
    page.evaluate("wp.data.dispatch('core/editor').editPost({title:'Info', status:'publish'})")
    page.evaluate("wp.data.dispatch('core/editor').savePost()")
    page.wait_for_function("!wp.data.select('core/editor').isSavingPost() && wp.data.select('core/editor').getCurrentPost().status==='publish'", timeout=30000)
    link = page.evaluate("wp.data.select('core/editor').getPermalink()")
    for sel in ("button[aria-label='Close']", ".components-modal__header button"):
        if page.locator(".components-modal__frame").count() and page.locator(sel).count():
            page.locator(sel).first.click()
            page.wait_for_timeout(500)
    page.evaluate("wp.data.dispatch('core/editor').setIsInserterOpened(false)")
    page.wait_for_timeout(1500)
    page.screenshot(path=SHOT + "/screenshot-2.png")
    ok("Seite veroeffentlicht (nur im Test-WordPress)")
    check(not any("open-door-ai" in e or "open_door" in e for e in errors), "keine JS-Fehler des Plugins im Editor")

    # 6. front end
    page.goto(link)
    check(page.locator(".open-door-ai-ask").count() == 1, "Block auf der Seite sichtbar")
    check("What does Meier Dach GmbH offer?" in page.inner_text(".open-door-ai-ask"), "Frage mit Firmennamen")
    check(page.locator(".open-door-ai-ask__foot").count() == 0, "kein flowboxx-Link ohne Opt-in")
    page.screenshot(path=SHOT + "/screenshot-3.png")

    other = [e for e in errors if "open-door-ai" not in e]
    print("INFO weitere Konsolenmeldungen (WordPress-Kern, nicht Plugin):", len(other))
    for e in errors[:8]:
        print("   ", e[:200])
    b.close()

print("ENDE fail=%d" % len(FAIL))
sys.exit(1 if FAIL else 0)
