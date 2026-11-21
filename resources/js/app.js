const toggle = document.querySelector('[data-nav-toggle]');
const menu = document.querySelector('[data-nav-menu]');

toggle?.addEventListener('click', () => {
    const open = menu.classList.toggle('hidden') === false;
    toggle.setAttribute('aria-expanded', String(open));
});

const dropdowns = () => document.querySelectorAll('details[data-dropdown][open]');

document.addEventListener('click', (e) => {
    dropdowns().forEach((d) => { if (!d.contains(e.target)) d.removeAttribute('open'); });
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') dropdowns().forEach((d) => d.removeAttribute('open'));
});