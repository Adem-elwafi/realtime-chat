function getCookie(name) {
    const match = document.cookie.match(new RegExp(`(?:^|;\\s*)${encodeURIComponent(name)}=([^;]*)`));
    return match ? decodeURIComponent(match[1]) : '';
}

async function refreshCsrfToken() {
    await fetch('/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'include',
        headers: { 'Accept': 'application/json' },
    });

    return getCookie('XSRF-TOKEN');
}

function collectFormData(form) {
    const data = new URLSearchParams();
    const formData = new FormData(form);

    for (const [key, value] of formData.entries()) {
        if (key === '_token') {
            continue;
        }
        data.append(key, value);
    }

    return data;
}

function clearErrors(form) {
    form.querySelectorAll('[data-input-error-for]').forEach((el) => {
        el.textContent = '';
    });
}

function showErrors(form, errors) {
    clearErrors(form);

    if (!errors || typeof errors !== 'object') {
        return;
    }

    for (const [field, messages] of Object.entries(errors)) {
        const container = form.querySelector(`[data-input-error-for="${field}"]`);
        if (!container) {
            continue;
        }
        for (const message of messages) {
            const item = document.createElement('li');
            item.textContent = message;
            container.appendChild(item);
        }
    }
}

function showAlert(form, message) {
    const alert = form.querySelector('[data-form-alert]');
    if (!alert) {
        return;
    }
    alert.textContent = message;
    alert.classList.remove('hidden');
}

function hideAlert(form) {
    const alert = form.querySelector('[data-form-alert]');
    if (alert) {
        alert.classList.add('hidden');
    }
}

function setLoading(button, loading) {
    if (!button) {
        return;
    }
    button.disabled = loading;
    button.setAttribute('aria-disabled', loading ? 'true' : 'false');
}

async function postWithFreshCsrf(form) {
    const button = form.querySelector('[type="submit"]');
    const originalLabel = button ? button.textContent : '';
    const action = form.getAttribute('action');

    hideAlert(form);
    clearErrors(form);
    setLoading(button, true);

    try {
        const xsrfToken = await refreshCsrfToken();

        const response = await fetch(action, {
            method: 'POST',
            credentials: 'include',
            redirect: 'manual',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken,
            },
            body: collectFormData(form),
        });

        if (response.type === 'opaqueredirect' || (response.status >= 300 && response.status < 400)) {
            window.location.href = response.headers.get('Location') || '/';
            return;
        }

        if (response.status === 419) {
            const retryToken = await refreshCsrfToken();

            const retry = await fetch(action, {
                method: 'POST',
                credentials: 'include',
                redirect: 'manual',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': retryToken,
                },
                body: collectFormData(form),
            });

            if (retry.type === 'opaqueredirect' || (retry.status >= 300 && retry.status < 400)) {
                window.location.href = retry.headers.get('Location') || '/';
                return;
            }

            if (retry.status === 422) {
                const json = await retry.json();
                showErrors(form, json.errors || {});
                return;
            }

            if (retry.status === 419) {
                showAlert(form, 'Your session expired. Please refresh the page and try again.');
                return;
            }

            showAlert(form, 'Something went wrong. Please try again.');
            return;
        }

        if (response.status === 422) {
            const json = await response.json();
            showErrors(form, json.errors || {});
            return;
        }

        if (response.ok) {
            window.location.href = response.headers.get('Location') || '/';
            return;
        }

        showAlert(form, 'Something went wrong. Please try again.');
    } catch (error) {
        showAlert(form, 'Network error. Please try again.');
    } finally {
        setLoading(button, false);
        if (button) {
            button.textContent = originalLabel;
        }
    }
}

function initAuthForms() {
    document.querySelectorAll('form[data-fresh-csrf]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            postWithFreshCsrf(form);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAuthForms);
} else {
    initAuthForms();
}
