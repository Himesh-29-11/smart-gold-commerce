import './bootstrap';
import '../css/gold-dashboard.css';
import '../css/catalog.css';
import '../css/forms.css';
import '../css/admin.css';
import '../css/tracking.css';
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

const renderUploadPreview = (target, files, replace = false) => {
    if (!target) return;
    if (replace) target.replaceChildren();

    [...files].forEach(file => {
        const item = document.createElement('span');
        item.className = 'upload-preview-item';
        if (file.type.startsWith('image/')) {
            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = '';
            image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });
            item.append(image);
        } else {
            const icon = document.createElement('i');
            icon.textContent = '▶';
            item.append(icon);
        }
        const name = document.createElement('small');
        name.textContent = file.name;
        item.append(name);
        target.append(item);
    });
};

document.querySelectorAll('[data-upload-dropzone]').forEach(zone => {
    const input = zone.querySelector('input[type="file"]');
    const preview = document.getElementById(zone.dataset.previewTarget);
    if (!input) return;

    const applyFiles = files => {
        const transfer = new DataTransfer();
        const incoming = [...files];
        const combined = input.multiple ? [...input.files, ...incoming] : incoming.slice(0, 1);
        combined.filter((file, index, list) => list.findIndex(candidate => candidate.name === file.name && candidate.size === file.size) === index)
            .forEach(file => transfer.items.add(file));
        input.files = transfer.files;
        renderUploadPreview(preview, input.files, true);
    };

    ['dragenter', 'dragover'].forEach(eventName => zone.addEventListener(eventName, event => {
        event.preventDefault();
        zone.classList.add('dragging');
    }));
    ['dragleave', 'drop'].forEach(eventName => zone.addEventListener(eventName, event => {
        event.preventDefault();
        zone.classList.remove('dragging');
    }));
    zone.addEventListener('drop', event => applyFiles(event.dataTransfer.files));
    input.addEventListener('change', () => renderUploadPreview(preview, input.files, true));
});

document.querySelectorAll('[data-folder-upload]').forEach(input => {
    const preview = document.getElementById(input.dataset.previewTarget);
    input.addEventListener('change', () => renderUploadPreview(preview, input.files, false));
});

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
