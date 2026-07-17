import './bootstrap';
import '../css/gold-dashboard.css';
import '../css/catalog.css';
import '../css/forms.css';
import '../css/admin.css';
import Chart from 'chart.js/auto';

window.Chart = Chart;

const navToggle = document.querySelector('[data-nav-toggle]');
const nav = document.querySelector('[data-nav]');
navToggle?.addEventListener('click', () => nav?.classList.toggle('open'));

const adminSidebar = document.querySelector('[data-admin-sidebar]');
const adminBackdrop = document.querySelector('[data-admin-nav-close]');
const setAdminNavigation = open => {
    adminSidebar?.classList.toggle('open', open);
    adminBackdrop?.classList.toggle('open', open);
    document.body.classList.toggle('admin-navigation-open', open);
};
document.querySelector('[data-admin-nav-toggle]')?.addEventListener('click', () => setAdminNavigation(true));
adminBackdrop?.addEventListener('click', () => setAdminNavigation(false));
adminSidebar?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setAdminNavigation(false)));

document.querySelectorAll('[data-password-toggle]').forEach(button => {
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;

    const showLabel = button.getAttribute('aria-label') || 'Show password';
    const hideLabel = showLabel.replace(/^Show/i, 'Hide');

    button.addEventListener('click', () => {
        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        button.setAttribute('aria-pressed', reveal ? 'true' : 'false');
        button.setAttribute('aria-label', reveal ? hideLabel : showLabel);
        button.setAttribute('title', reveal ? hideLabel : showLabel);
    });
});

document.querySelectorAll('[data-emi-calculator]').forEach(() => {
    const amount = document.getElementById('loanAmount');
    const rate = document.getElementById('loanRate');
    const tenure = document.getElementById('loanTenure');
    const amountOutput = document.getElementById('amountOutput');
    const rateOutput = document.getElementById('rateOutput');
    const tenureOutput = document.getElementById('tenureOutput');
    const emiOutput = document.getElementById('emiOutput');
    const interestOutput = document.getElementById('interestOutput');
    const money = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 });

    const calculate = () => {
        const principal = Number(amount.value);
        const annualRate = Number(rate.value);
        const months = Number(tenure.value);
        const monthlyRate = annualRate / 1200;
        const emi = monthlyRate > 0
            ? principal * monthlyRate * ((1 + monthlyRate) ** months) / (((1 + monthlyRate) ** months) - 1)
            : principal / months;
        amountOutput.textContent = money.format(principal);
        rateOutput.textContent = `${annualRate}%`;
        tenureOutput.textContent = `${months} months`;
        emiOutput.textContent = money.format(emi);
        interestOutput.textContent = `Approx. total interest ${money.format((emi * months) - principal)}`;
    };

    [amount, rate, tenure].forEach(input => input?.addEventListener('input', calculate));
    calculate();
});

// Prevent accidental duplicate submissions on high-value write actions.
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        if (button && !form.hasAttribute('data-allow-repeat')) {
            window.setTimeout(() => { button.disabled = true; button.setAttribute('aria-busy', 'true'); }, 0);
        }
    });
});
