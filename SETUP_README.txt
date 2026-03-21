====================================================================
  SIET-LMS — Safe Exam Browser Portal
  Sri Shakthi Institute of Engineering and Technology
  Setup & Usage Guide  |  Folder: SEB-LMS-portal
====================================================================

── INSTALLATION ──────────────────────────────────────────────────

1. Copy this folder to: C:\xampp\htdocs\SEB-LMS-portal\
   (folder name MUST be exactly: SEB-LMS-portal)

2. Start XAMPP → Start Apache + MySQL

3. Import database:
   - Open http://localhost/phpmyadmin
   - Create database: seb_lms
   - Import: database.sql

4. Install Safe Exam Browser (if not installed):
   https://safeexambrowser.org/download_en.html

── COMPLETE STUDENT FLOW ─────────────────────────────────────────

STEP 1 — Normal browser (Chrome/Edge/Firefox):
  Open: http://localhost/SEB-LMS-portal/login.php
  → Login with credentials
  → Mode Selection page appears

STEP 2 — Click a mode:
  ┌─ Practice Mode ─────────────────────────────────────────────┐
  │  → launch_practice.php opens (normal browser)              │
  │  → Shows "Launch Safe Exam Browser" button                 │
  │  → Student clicks → SEB opens with EASY config            │
  │  → SEB starts at: practice_seb_entry.php                  │
  │  → If not logged in → login_seb.php (inside SEB)          │
  │  → practice.php loads (subject list)                       │
  │  → "Exit SEB" button → seb_quit.php → SEB closes          │
  └─────────────────────────────────────────────────────────────┘
  ┌─ Exam Mode ─────────────────────────────────────────────────┐
  │  → launch_exam.php opens (normal browser)                  │
  │  → Shows "Launch Safe Exam Browser" button                 │
  │  → Student clicks → SEB opens with STRICT/KIOSK config    │
  │  → SEB starts at: exam_select.php                         │
  │  → If not logged in → login_seb.php?from=exam (inside SEB)│
  │  → exam_select.php shows exam list                         │
  │  → Student selects exam → enters entry password            │
  │  → exam.php runs (full kiosk, 90 min timer)               │
  │  → Student submits with exit password                      │
  │  → exam_result.php → "Exit SEB" → SEB closes              │
  └─────────────────────────────────────────────────────────────┘

── WHICH PAGES ARE NORMAL BROWSER vs SEB ─────────────────────────

  NORMAL BROWSER (no SEB needed):
  ✓ login.php              — student login
  ✓ mode.php               — choose practice or exam
  ✓ launch_practice.php    — shows SEB launch button
  ✓ launch_exam.php        — shows SEB launch button

  SEB ONLY (requireSEB() blocks regular browsers):
  ✓ practice_seb_entry.php — SEB entry for practice
  ✓ login_seb.php          — login inside SEB (if session expired)
  ✓ practice.php           — subject list
  ✓ subject.php            — module list
  ✓ module.php             — coding problems
  ✓ editor.php             — code editor
  ✓ exam_select.php        — exam list (inside SEB)
  ✓ exam.php               — exam environment
  ✓ exam_result.php        — results after exam

── SEB CONFIGURATION SUMMARY ────────────────────────────────────

  practice_mode.seb (Easy)
  ──────────────────────────
  • Start URL: .../practice_seb_entry.php
  • Window: Maximized (not full kiosk)
  • No address bar, no new tabs
  • URL filter: localhost only (+ Google Fonts)
  • Blocked: Print Screen, Alt+Tab, Alt+F4, right-click
  • Quit password: exit456

  exam_mode.seb (Strict / Maximum Security)
  ──────────────────────────────────────────
  • Start URL: .../exam_select.php
  • Window: Full-screen KIOSK (browserViewMode=1)
  • URL filter: localhost only (+ Google Fonts)
  • Blocked: Alt+Tab, Alt+F4, Alt+Esc, Ctrl+Esc
  • Blocked: Print Screen, right-click, F5, zoom
  • Blocked: Start menu, task bar, WLAN
  • Max 1 display
  • Quit password: exit456

── PASSWORDS ─────────────────────────────────────────────────────

  SEB Quit Password : exit456
  Demo Student Login: demo / demo
  Exam Entry/Exit   : set per-exam in Admin panel

── ADMIN PANEL ───────────────────────────────────────────────────

  URL: http://localhost/SEB-LMS-portal/admin/
  (Admin panel does NOT require SEB — teacher use only)

====================================================================
