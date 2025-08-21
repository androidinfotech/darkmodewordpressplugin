<?php
/*
Plugin Name: Genesis Dark Mode Toggle (single-file)
Description: Adds Dark/Light toggle to Genesis Sample nav, auto-switches with system preference. Inline CSS/JS for reliability.
Version: 1.1
Author: (your name)
*/

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'gdmt_enqueue_assets', 20);
function gdmt_enqueue_assets() {
    // Register and enqueue an "empty" style so we can add inline CSS that loads after the theme.
    wp_register_style('gdmt-style', false);
    wp_enqueue_style('gdmt-style');

    $css = <<<'CSS'
/* Toggle button basic */
.gdmt-switch {
  background: transparent;
  border: none;
  font-size: 18px;
  cursor: pointer;
  color: inherit;
  padding: 6px 8px;
}

/* Floating fallback button */
.gdmt-floating {
  position: fixed;
  right: 16px;
  bottom: 16px;
  z-index: 99999;
  border-radius: 6px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
  background: rgba(255,255,255,0.05);
  backdrop-filter: blur(6px);
}

/* Smooth transitions for visible properties */
html[data-theme] *, html[data-theme]::before, html[data-theme]::after {
  transition: background-color 0.22s ease, color 0.22s ease, border-color 0.22s ease;
}

/* Dark mode overrides using html[data-theme="dark"] for top-level specificity */
html[data-theme="dark"] {
  background: #0f1113 !important;
  color: #e6e6e6 !important;
}

/* Common containers - ensure enough specificity and fallback with !important where theme overrides */
html[data-theme="dark"] body,
html[data-theme="dark"] .site,
html[data-theme="dark"] .site-inner,
html[data-theme="dark"] .site-header,
html[data-theme="dark"] .nav-primary,
html[data-theme="dark"] .wrap,
html[data-theme="dark"] .site-main,
html[data-theme="dark"] .content,
html[data-theme="dark"] .site-footer,
html[data-theme="dark"] .widget,
html[data-theme="dark"] .sidebar {
  background-color: #111214 !important;
  color: #e6e6e6 !important;
}

/* Articles/entries */
html[data-theme="dark"] article,
html[data-theme="dark"] .entry,
html[data-theme="dark"] .entry-content,
html[data-theme="dark"] .content-inner {
  background-color: transparent !important;
  color: #e6e6e6 !important;
}

/* Headings & Links */
html[data-theme="dark"] h1, html[data-theme="dark"] h2, html[data-theme="dark"] h3,
html[data-theme="dark"] h4, html[data-theme="dark"] h5, html[data-theme="dark"] h6 {
  color: #ffffff !important;
}
html[data-theme="dark"] a, html[data-theme="dark"] .entry-title a {
  color: #8fc3ff !important;
}
html[data-theme="dark"] a:hover { color: #b6dbff !important; }

/* Navigation specifics */
html[data-theme="dark"] .nav-primary,
html[data-theme="dark"] .genesis-nav-menu {
  background: #121214 !important;
  color: #f3f3f3 !important;
}

/* Buttons & inputs */
html[data-theme="dark"] button,
html[data-theme="dark"] input,
html[data-theme="dark"] input[type="submit"],
html[data-theme="dark"] .button {
  background-color: #242627 !important;
  color: #eaeaea !important;
  border-color: #333 !important;
}

/* Forms and widgets */
html[data-theme="dark"] .widget, html[data-theme="dark"] .widget .widget-title {
  color: #e6e6e6 !important;
}

/* Make sure images keep natural colors (don't invert) */
html[data-theme="dark"] img { filter: none !important; }

/* Style for small toggle inside menu if it is a <li> element */
.dlmt-toggle { list-style: none; display: inline-flex; align-items: center; margin-left: 6px; }
.dlmt-toggle .gdmt-switch { font-size: 16px; }
CSS;

    wp_add_inline_style('gdmt-style', $css);

    // Register an empty script handle (so we can add inline script reliably)
    wp_register_script('gdmt-script', false, array(), null, true);
    wp_enqueue_script('gdmt-script');

    $js = <<<'JS'
(function(){
  const STORAGE_KEY = 'gdmt-mode';
  const BTN_ID = 'gdmt-switch';

  function applyMode(mode) {
    if(mode === 'dark') {
      document.documentElement.setAttribute('data-theme','dark');
    } else {
      document.documentElement.setAttribute('data-theme','light');
    }
    updateButton(mode === 'dark');
  }

  function updateButton(isDark){
    const btn = document.getElementById(BTN_ID);
    if(!btn) return;
    btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    btn.textContent = isDark ? '🌙' : '☀️';
  }

  function createToggleInside(selector) {
    const container = document.querySelector(selector);
    if(!container) return false;

    // If container is a UL (menu) append LI, else append the button directly
    const btn = document.createElement('button');
    btn.id = BTN_ID;
    btn.className = 'gdmt-switch';
    btn.setAttribute('aria-label', 'Toggle dark mode');

    if(container.tagName.toLowerCase() === 'ul' || container.tagName.toLowerCase() === 'ol') {
      const li = document.createElement('li');
      li.className = 'menu-item dlmt-toggle';
      li.style.listStyle = 'none';
      li.appendChild(btn);
      container.appendChild(li);
    } else {
      // place in container
      const wrapper = document.createElement('div');
      wrapper.className = 'dlmt-toggle';
      wrapper.style.display = 'inline-flex';
      wrapper.appendChild(btn);
      container.appendChild(wrapper);
    }
    return true;
  }

  function ensureToggleExists() {
    // Try a set of selectors used by Genesis Sample and common header/nav layouts
    const selectors = [
      '.nav-primary .menu',
      '.nav-primary',
      '.genesis-nav-menu',
      '.main-navigation .menu',
      '.site-header .wrap',
      '.site-header'
    ];
    for(const s of selectors) {
      if (document.querySelector(s) && !document.getElementById(BTN_ID)) {
        if(createToggleInside(s)) return;
      }
    }
    // fallback: floating button appended to body
    if(!document.getElementById(BTN_ID)) {
      const fb = document.createElement('button');
      fb.id = BTN_ID;
      fb.className = 'gdmt-switch gdmt-floating';
      fb.setAttribute('aria-label', 'Toggle dark mode');
      document.body.appendChild(fb);
    }
  }

  function init() {
    ensureToggleExists();

    const btn = document.getElementById(BTN_ID);
    if(!btn) return;

    // initial mode: saved -> system -> light
    const saved = localStorage.getItem(STORAGE_KEY);
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initial = saved ? saved : (prefersDark ? 'dark' : 'light');
    applyMode(initial);

    btn.addEventListener('click', function(){
      const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      const next = current === 'dark' ? 'light' : 'dark';
      localStorage.setItem(STORAGE_KEY, next);
      applyMode(next);
    });

    // If user hasn't chosen, follow system changes
    if(window.matchMedia) {
      const mq = window.matchMedia('(prefers-color-scheme: dark)');
      const listener = (e) => {
        if(!localStorage.getItem(STORAGE_KEY)) {
          applyMode(e.matches ? 'dark' : 'light');
        }
      };
      if(typeof mq.addEventListener === 'function') mq.addEventListener('change', listener);
      else if(typeof mq.addListener === 'function') mq.addListener(listener);
    }
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
JS;

    wp_add_inline_script('gdmt-script', $js);
}

// Try to inject toggle into menus server-side for 'primary' locations (best-effort)
add_filter('wp_nav_menu_items', 'gdmt_add_toggle_to_menu', 10, 2);
function gdmt_add_toggle_to_menu($items, $args) {
    // known "primary" names used by themes (Genesis Sample uses 'primary')
    $primary_names = array('primary','main','primary-menu','menu-1','genesis-primary-nav');
    if(isset($args->theme_location) && in_array($args->theme_location, $primary_names)) {
        $items .= '<li class="menu-item dlmt-toggle"><button id="gdmt-switch" class="gdmt-switch" aria-label="Toggle dark mode">🌓</button></li>';
    }
    return $items;
}
