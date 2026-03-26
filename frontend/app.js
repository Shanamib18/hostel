const API_URL = 'http://localhost:3000/api';

const $ = (sel, el = document) => el.querySelector(sel);
const $$ = (sel, el = document) => el.querySelectorAll(sel);

function getToken() {
  return localStorage.getItem('hostel_token');
}
function setToken(t) {
  if (t) localStorage.setItem('hostel_token', t);
  else localStorage.removeItem('hostel_token');
}
function getUser() {
  try {
    return JSON.parse(localStorage.getItem('hostel_user') || '{}');
  } catch { return {}; }
}
function setUser(u) {
  localStorage.setItem('hostel_user', JSON.stringify(u || {}));
}

async function api(path, opts = {}) {
  const token = getToken();
  const res = await fetch(API_URL + path, {
    ...opts,
    headers: {
      'Content-Type': 'application/json',
      ...(token && { Authorization: `Bearer ${token}` }),
      ...opts.headers,
    },
    body: opts.body ? JSON.stringify(opts.body) : opts.body,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || res.statusText);
  return data;
}

function showPage(id) {
  $$('.page').forEach(p => p.style.display = 'none');
  const page = $(`#${id}`);
  if (page) page.style.display = 'block';
}

function showLoggedIn(user) {
  setUser(user);
  $('#loginLink').style.display = 'none';
  $('#userArea').style.display = 'flex';
  $('#userName').textContent = user.name || user.email;
  if (user.student_id) {
    showPage('studentDashboard');
    loadStudentData();
  } else {
    window.location.href = 'php/dashboard.php';
  }
}

function showLoggedOut() {
  setToken(null);
  setUser({});
  $('#userArea').style.display = 'none';
  $('#loginLink').style.display = 'inline';
  showPage('homePage');
}

async function login(e) {
  e.preventDefault();
  const type = $('#loginType').value;
  const email = $('#loginEmail').value;
  const password = $('#loginPassword').value;
  const errEl = $('#loginError');
  errEl.style.display = 'none';
  try {
    const res = await api(`/auth/${type}/login`, {
      method: 'POST',
      body: { email, password },
    });
    setToken(res.token);
    showLoggedIn(res.user);
  } catch (err) {
    errEl.textContent = err.message || 'Login failed';
    errEl.style.display = 'block';
  }
}

async function handleForgotPassword(e) {
  e.preventDefault();
  const email = $('#forgotEmail').value;
  const errEl = $('#forgotError');
  const succEl = $('#forgotSuccess');
  errEl.style.display = 'none';
  succEl.style.display = 'none';

  try {
    const res = await api('/auth/forgot-password', {
      method: 'POST',
      body: { email }
    });
    succEl.textContent = res.message;
    succEl.style.display = 'block';
  } catch (err) {
    errEl.textContent = err.message || 'Failed to send request';
    errEl.style.display = 'block';
  }
}

async function handleResetPassword(e) {
  e.preventDefault();
  const token = $('#resetToken').value;
  const password = $('#newPassword').value;
  
  try {
    const res = await api('/auth/reset-password', {
      method: 'POST',
      body: { token, password }
    });
    alert(res.message);
    showPage('loginPage');
    window.history.replaceState({}, document.title, "/"); // Clear URL
  } catch (err) {
    alert(err.message);
  }
}

async function loadStudentData() {
  try {
    const [mess, entryExit, feeStatus, payments] = await Promise.all([
      api('/attendance/mess?start=' + new Date().toISOString().slice(0,10) + '&end=' + new Date().toISOString().slice(0,10)),
      api('/attendance/entry-exit?start=' + new Date().toISOString().slice(0,10) + '&end=' + new Date().toISOString().slice(0,10)),
      api('/payments/dues'),
      api('/payments/my-payments'),
    ]);

    const messList = $('#messList');
    messList.innerHTML = mess.length ? `
      <table><thead><tr><th>Meal</th><th>Date/Time</th><th>Method</th></tr></thead>
      <tbody>${mess.map(m => `<tr><td>${m.meal_type}</td><td>${new Date(m.marked_at).toLocaleString()}</td><td>${m.method}</td></tr>`).join('')}</tbody></table>
    ` : '<p class="empty">No mess attendance records for today.</p>';

    const eeList = $('#entryExitList');
    eeList.innerHTML = entryExit.length ? `
      <table><thead><tr><th>Type</th><th>Date/Time</th><th>Method</th></tr></thead>
      <tbody>${entryExit.map(e => `<tr><td>${e.type}</td><td>${new Date(e.recorded_at).toLocaleString()}</td><td>${e.method}</td></tr>`).join('')}</tbody></table>
    ` : '<p class="empty">No entry/exit records for today.</p>';

    const duesList = $('#feeStructure'); // This element's content will be replaced
    duesList.parentElement.querySelector('h2').textContent = 'Pending Dues'; // Update heading
    duesList.innerHTML = feeStatus.length ? `
      <table><thead><tr><th>Fee Type</th><th>Amount</th><th>Due For</th><th>Notes</th></tr></thead>
      <tbody>${feeStatus.map(due => `
        <tr>
          <td>${due.fee_type}</td>
          <td>₹${Number(due.amount).toFixed(2)}</td>
          <td>${new Date(due.payment_date).toLocaleDateString('en-GB', { month: 'long', year: 'numeric' })}</td>
          <td>${due.notes || ''}</td>
        </tr>`).join('')}</tbody></table>
    ` : '<p class="empty">No pending dues found.</p>';

    const payList = $('#myPayments');
    payList.innerHTML = payments.length ? `
      <table><thead><tr><th>Fee</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>${payments.map(p => `<tr><td>${p.fee_type || '-'}</td><td>₹${p.amount}</td><td>${p.payment_date}</td><td>${p.status}</td></tr>`).join('')}</tbody></table>
    ` : '<p class="empty">No payments yet.</p>';
  } catch (err) {
    console.error(err);
  }
}

function init() {
  const resetToken = new URLSearchParams(window.location.search).get('reset_token');

  if (resetToken) {
    showPage('resetPasswordPage');
    $('#resetToken').value = resetToken;
  } else if (getToken() && getUser().student_id) {
    showLoggedIn(getUser());
  } else if (getToken()) {
    showLoggedOut();
  }

  $('#loginForm')?.addEventListener('submit', login);
  $('#logoutBtn')?.addEventListener('click', showLoggedOut);

  $('#loginLink')?.addEventListener('click', (e) => { e.preventDefault(); showPage('loginPage'); });
  $('#forgotPasswordLink')?.addEventListener('click', (e) => { e.preventDefault(); showPage('forgotPasswordPage'); });
  $('#backToLoginLink')?.addEventListener('click', (e) => { e.preventDefault(); showPage('loginPage'); });
  $('#forgotPasswordForm')?.addEventListener('submit', handleForgotPassword);
  $('#resetPasswordForm')?.addEventListener('submit', handleResetPassword);
  $('[data-page="home"]')?.addEventListener('click', (e) => { e.preventDefault(); showPage('homePage'); });

  $$('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      $$('.tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      $('#attendanceTab').style.display = tab.dataset.tab === 'attendance' ? 'block' : 'none';
      $('#paymentsTab').style.display = tab.dataset.tab === 'payments' ? 'block' : 'none';
    });
  });

  $('#payFeeBtn')?.addEventListener('click', () => {
    alert('Payment integration: In production, this would redirect to payment gateway (UPI/Net Banking).');
  });
}

init();
