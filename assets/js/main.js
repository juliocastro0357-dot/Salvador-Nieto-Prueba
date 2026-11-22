/* Salvador Nieto · El Noble de la Banda — comportamiento del sitio */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const api = (p) => (window.SN && SN.base ? SN.base : '') + '/' + p;

  /* ---------- header ---------- */
  const header = $('#header');
  const onScroll = () => header && header.classList.toggle('scrolled', window.scrollY > 10);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  const burger = $('#burger'), nav = $('#nav');
  if (burger && nav) {
    burger.addEventListener('click', () => {
      const open = nav.classList.toggle('open');
      burger.setAttribute('aria-expanded', open);
    });
    nav.addEventListener('click', (e) => { if (e.target.closest('a')) nav.classList.remove('open'); });
  }

  /* ---------- aparición al hacer scroll ---------- */
  const reveals = $$('.reveal');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    reveals.forEach((el, i) => { el.style.transitionDelay = ((i % 4) * 70) + 'ms'; io.observe(el); });
  } else {
    reveals.forEach((el) => el.classList.add('in'));
  }

  /* ---------- toast ---------- */
  const toastEl = $('#toast');
  let toastTimer;
  const toast = (html, kind = 'ok') => {
    if (!toastEl) return;
    toastEl.className = 'toast show ' + kind;
    toastEl.innerHTML = html;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastEl.classList.remove('show'), 4200);
  };

  /* ---------- reproductor de 30 segundos ---------- */
  const LIMIT = 30;
  const mp = $('#miniplayer');
  if (mp) {
    const audio = new Audio();
    audio.preload = 'none';
    const els = {
      cover: $('#mpCover'), title: $('#mpTitle'), toggle: $('#mpToggle'), bar: $('#mpBar'),
      prog: $('#mpProgress'), time: $('#mpTime'), link: $('#mpLink'), close: $('#mpClose'),
    };
    const icoPlay = els.toggle.innerHTML;
    const icoPause = '<svg class="ico" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><rect x="6.5" y="5" width="4" height="14" rx="1" fill="currentColor"/><rect x="13.5" y="5" width="4" height="14" rx="1" fill="currentColor"/></svg>';
    let currentBtn = null;
    const fmt = (s) => Math.floor(s / 60) + ':' + String(Math.floor(s % 60)).padStart(2, '0');

    const setPlaying = (on) => {
      els.toggle.innerHTML = on ? icoPause : icoPlay;
      $$('.is-playing').forEach((b) => b.classList.remove('is-playing'));
      if (on && currentBtn) currentBtn.classList.add('is-playing');
    };
    const stopAtLimit = () => {
      audio.pause();
      audio.currentTime = 0;
      setPlaying(false);
      els.prog.style.width = '100%';
      els.time.textContent = '0:30 / 0:30';
    };

    audio.addEventListener('timeupdate', () => {
      const t = Math.min(audio.currentTime, LIMIT);
      els.prog.style.width = (t / LIMIT * 100) + '%';
      els.time.textContent = fmt(t) + ' / 0:30';
      els.bar.setAttribute('aria-valuenow', Math.floor(t));
      if (audio.currentTime >= LIMIT) stopAtLimit();
    });
    audio.addEventListener('ended', stopAtLimit);
    audio.addEventListener('pause', () => setPlaying(false));
    audio.addEventListener('play', () => setPlaying(true));
    audio.addEventListener('error', () => { setPlaying(false); toast('No pudimos cargar la vista previa. Escúchala completa en tu plataforma favorita.', 'bad'); });

    const load = (btn) => {
      const d = btn.dataset;
      if (!d.src) { if (d.link) window.open(d.link, '_blank', 'noopener'); return; }
      const same = audio.src && audio.src.endsWith(d.src.replace(/^.*\/\/[^/]+/, ''));
      currentBtn = btn;
      mp.hidden = false;
      document.body.classList.add('has-player');
      if (same && !audio.ended) {
        audio.paused ? audio.play() : audio.pause();
        return;
      }
      audio.src = d.src;
      els.cover.src = d.cover || '';
      els.title.textContent = d.title || '';
      if (d.link) { els.link.href = d.link; els.link.hidden = false; } else { els.link.hidden = true; }
      els.prog.style.width = '0';
      els.time.textContent = '0:00 / 0:30';
      audio.play().catch(() => {});
    };

    document.addEventListener('click', (e) => {
      const b = e.target.closest('[data-play]');
      if (b) { e.preventDefault(); load(b); }
    });
    els.toggle.addEventListener('click', () => {
      if (!audio.src) return;
      if (audio.currentTime >= LIMIT) audio.currentTime = 0;
      audio.paused ? audio.play() : audio.pause();
    });
    els.bar.addEventListener('click', (e) => {
      if (!audio.src) return;
      const r = els.bar.getBoundingClientRect();
      audio.currentTime = Math.max(0, Math.min(LIMIT - 0.1, ((e.clientX - r.left) / r.width) * LIMIT));
    });
    els.close.addEventListener('click', () => {
      audio.pause();
      audio.removeAttribute('src');
      audio.load();
      mp.hidden = true;
      document.body.classList.remove('has-player');
      setPlaying(false);
    });
    document.addEventListener('keydown', (e) => {
      if (e.code === 'Space' && !mp.hidden && !/INPUT|TEXTAREA|SELECT|BUTTON|A/.test(document.activeElement.tagName)) {
        e.preventDefault(); els.toggle.click();
      }
    });
  }

  /* ---------- cantidad (+ / −) ---------- */
  $$('[data-qty]').forEach((box) => {
    const input = $('input', box);
    const step = (d) => {
      const min = +input.min || 1, max = +input.max || 10;
      input.value = Math.max(min, Math.min(max, (+input.value || 1) + d));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    $('[data-dec]', box).addEventListener('click', () => step(-1));
    $('[data-inc]', box).addEventListener('click', () => step(1));
  });

  /* ---------- galería de producto ---------- */
  const main = $('#galMain');
  $$('.thumb').forEach((t) => t.addEventListener('click', () => {
    if (main) main.src = t.dataset.img;
    $$('.thumb').forEach((x) => x.classList.toggle('on', x === t));
  }));

  /* ---------- agregar al carrito sin recargar ---------- */
  const buy = $('#buyForm');
  if (buy && window.fetch) {
    buy.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = $('button[type=submit]', buy);
      btn.disabled = true;
      try {
        const res = await fetch(api('api/cart.php'), { method: 'POST', body: new FormData(buy), headers: { 'X-CSRF-Token': SN.csrf } });
        const j = await res.json();
        if (j.ok) {
          const c = $('#cartCount');
          if (c) { c.textContent = j.count; c.hidden = false; }
          toast('Agregado al carrito.<a href="' + api('carrito.php') + '">Ver carrito</a>');
        } else {
          toast(j.message || 'No se pudo agregar.', 'bad');
        }
      } catch (err) {
        buy.submit();
      }
      btn.disabled = false;
    });
  }

  /* ---------- Círculo del Noble ---------- */
  const nf = $('#newsForm');
  if (nf && window.fetch) {
    nf.addEventListener('submit', async (e) => {
      e.preventDefault();
      const msg = $('#newsMsg');
      msg.className = 'news-msg';
      try {
        const res = await fetch(nf.action, { method: 'POST', body: new FormData(nf), headers: { 'X-CSRF-Token': SN.csrf } });
        const j = await res.json();
        msg.textContent = j.message;
        msg.classList.add(j.ok ? 'ok' : 'bad');
        if (j.ok) nf.reset();
      } catch (err) {
        msg.textContent = 'No se pudo enviar. Inténtalo de nuevo.';
        msg.classList.add('bad');
      }
    });
  }

  /* ---------- videos de YouTube (carga bajo demanda) ---------- */
  $$('.video-frame[data-yt]').forEach((f) => {
    f.addEventListener('click', () => {
      const id = f.dataset.yt.replace(/[^\w-]/g, '');
      f.innerHTML = '<iframe src="https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0" title="' + (f.dataset.title || 'Video') +
        '" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe>';
    }, { once: true });
  });

  /* ---------- copiar texto ---------- */
  $$('[data-copy]').forEach((b) => b.addEventListener('click', async () => {
    const t = $(b.dataset.copy);
    if (!t) return;
    try { await navigator.clipboard.writeText(t.textContent.trim()); toast('Biografía copiada.'); }
    catch (e) { toast('No se pudo copiar. Selecciona el texto manualmente.', 'bad'); }
  }));
})();
