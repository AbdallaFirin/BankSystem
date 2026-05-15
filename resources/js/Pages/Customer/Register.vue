<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    dob: '',
    gender: '',
    marital_status: '',
    nationality: '',
    religion: '',
    id_type: '',
    id_number: '',
    issue_date: '',
    expiry_date: '',
    primary_phone: '',
    secondary_phone: '',
    email: '',
    contact_method: 'SMS',
    region: '',
    district: '',
    street: '',
    employment_status: '',
    employer: '',
    income_range: '',
    funds_source: '',
    expected_txns: '',
    account_type: 'Saving account',
    home_branch: '',
    initial_deposit: '',
    kin_name: '',
    kin_relation: '',
    kin_phone: '',
    agreed_terms: false,
    agreed_notifications: false,
    agreed_accurate: false,
    signature_date: '',
    processed_by: '',
    processed_branch: '',
    registration_date: '',
    notes: '',
});

const submit = () => {
    form.post('/register', {
        preserveScroll: true,
    });
};

const selectAccount = (type) => {
    form.account_type = type;
};
</script>

<template>
  <Head title="Customer Registration" />

  <div class="shell pt-8 pb-16">
    <!-- Header -->
    <div class="hdr">
      <div class="hdr-left">
        <div class="bank-mark">
          <img src="/storage/images/MAin Logo.png" alt="Gobaad Bank" style="height:36px;width:auto;object-fit:contain;" />
          <span class="bank-id">Gobaad Bank</span>
        </div>
        <div class="form-heading">New Customer<br><em>Registration</em></div>
        <p class="form-sub" style="margin-top:10px">Complete all required fields. KYC documents must be verified before account activation.</p>
      </div>
      <div class="hdr-right">
        <div class="ref-tag"><i class="ti ti-hash" aria-hidden="true"></i> REF: CUS-2026-00841</div>
        <div style="margin-bottom:6px; font-size:11px; color:var(--text-muted); text-align:right">Step 3 of 4</div>
        <div class="progress-wrap">
          <div class="prog-step done"></div>
          <div class="prog-step done"></div>
          <div class="prog-step active"></div>
          <div class="prog-step"></div>
        </div>
      </div>
    </div>

    <div class="hdr-divider"></div>

    <form @submit.prevent="submit">
      <!-- 1. Personal Information -->
      <div class="section">
        <div class="section-hdr">
          <div class="section-icon"><i class="ti ti-user" aria-hidden="true"></i></div>
          <span class="section-title">Personal information</span>
          <span class="section-num">01</span>
        </div>
        <div class="grid g3">
          <div class="field">
            <label class="label">First name<span class="req">*</span></label>
            <input type="text" v-model="form.first_name" placeholder="e.g. Abdalla" required />
          </div>
          <div class="field">
            <label class="label">Middle name</label>
            <input type="text" v-model="form.middle_name" placeholder="Optional" />
          </div>
          <div class="field">
            <label class="label">Last name<span class="req">*</span></label>
            <input type="text" v-model="form.last_name" placeholder="e.g. Firin" required />
          </div>
        </div>
        <div class="grid g3 mt">
          <div class="field">
            <label class="label">Date of birth<span class="req">*</span></label>
            <input type="date" v-model="form.dob" required />
          </div>
          <div class="field">
            <label class="label">Gender<span class="req">*</span></label>
            <select v-model="form.gender" required><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option><option>Prefer not to say</option></select>
          </div>
          <div class="field">
            <label class="label">Marital status</label>
            <select v-model="form.marital_status"><option value="">Select</option><option>Single</option><option>Married</option><option>Divorced</option><option>Widowed</option></select>
          </div>
        </div>
        <div class="grid g2 mt">
          <div class="field">
            <label class="label">Nationality<span class="req">*</span></label>
            <select v-model="form.nationality" required><option value="">Select country</option><option>Somali</option><option>Kenyan</option><option>Ethiopian</option><option>Other</option></select>
          </div>
          <div class="field">
            <label class="label">Religion</label>
            <select v-model="form.religion"><option value="">Select</option><option>Islam</option><option>Christianity</option><option>Other</option><option>Prefer not to say</option></select>
          </div>
        </div>
      </div>

      <!-- 2. Identity -->
      <div class="section">
        <div class="section-hdr">
          <div class="section-icon"><i class="ti ti-id" aria-hidden="true"></i></div>
          <span class="section-title">Identity &amp; national ID</span>
          <span class="section-num">02</span>
        </div>
        <div class="grid g2">
          <div class="field">
            <label class="label">ID type<span class="req">*</span></label>
            <select v-model="form.id_type" required><option value="">Select</option><option>National ID Card</option><option>Passport</option><option>Driver's License</option><option>Voter Card</option><option>Refugee ID</option></select>
          </div>
          <div class="field">
            <label class="label">ID number<span class="req">*</span></label>
            <input type="text" v-model="form.id_number" placeholder="e.g. SOM-1234567" required />
          </div>
        </div>
        <div class="grid g2 mt">
          <div class="field">
            <label class="label">Issue date<span class="req">*</span></label>
            <input type="date" v-model="form.issue_date" required />
          </div>
          <div class="field">
            <label class="label">Expiry date</label>
            <input type="date" v-model="form.expiry_date" />
            <span class="hint">Leave empty if ID has no expiry</span>
          </div>
        </div>
      </div>

      <!-- 3. Contact -->
      <div class="section">
        <div class="section-hdr">
          <div class="section-icon"><i class="ti ti-phone" aria-hidden="true"></i></div>
          <span class="section-title">Contact information</span>
          <span class="section-num">03</span>
        </div>
        <div class="grid g2">
          <div class="field">
            <label class="label">Primary phone<span class="req">*</span></label>
            <input type="tel" v-model="form.primary_phone" placeholder="+252 61 000 0000" required />
          </div>
          <div class="field">
            <label class="label">Secondary phone</label>
            <input type="tel" v-model="form.secondary_phone" placeholder="Optional" />
          </div>
        </div>
        <div class="grid g2 mt">
          <div class="field">
            <label class="label">Email address</label>
            <input type="email" v-model="form.email" placeholder="example@email.com" />
          </div>
          <div class="field">
            <label class="label">Preferred contact method</label>
            <select v-model="form.contact_method"><option>SMS</option><option>Email</option><option>Both</option></select>
          </div>
        </div>
        <div class="grid g3 mt">
          <div class="field">
            <label class="label">Region / City<span class="req">*</span></label>
            <input type="text" v-model="form.region" placeholder="e.g. Garoowe" required />
          </div>
          <div class="field">
            <label class="label">District</label>
            <input type="text" v-model="form.district" placeholder="e.g. Nugaal" />
          </div>
          <div class="field">
            <label class="label">Street / Neighbourhood</label>
            <input type="text" v-model="form.street" placeholder="e.g. KM4 Road" />
          </div>
        </div>
      </div>

      <!-- 4. Employment -->
      <div class="section">
        <div class="section-hdr">
          <div class="section-icon"><i class="ti ti-briefcase" aria-hidden="true"></i></div>
          <span class="section-title">Employment &amp; financial profile</span>
          <span class="section-num">04</span>
        </div>
        <div class="grid g2">
          <div class="field">
            <label class="label">Employment status<span class="req">*</span></label>
            <select v-model="form.employment_status" required><option value="">Select</option><option>Employed (Private)</option><option>Employed (Government)</option><option>Self-employed</option><option>Business owner</option><option>Student</option><option>Unemployed</option><option>Retired</option></select>
          </div>
          <div class="field">
            <label class="label">Employer / Business name</label>
            <input type="text" v-model="form.employer" placeholder="If applicable" />
          </div>
        </div>
        <div class="grid g3 mt">
          <div class="field">
            <label class="label">Monthly income range<span class="req">*</span></label>
            <select v-model="form.income_range" required><option value="">Select</option><option>Under $200</option><option>$200 – $500</option><option>$500 – $1,000</option><option>$1,000 – $3,000</option><option>Above $3,000</option></select>
          </div>
          <div class="field">
            <label class="label">Source of funds<span class="req">*</span></label>
            <select v-model="form.funds_source" required><option value="">Select</option><option>Salary</option><option>Business income</option><option>Remittance</option><option>Savings</option><option>Investment</option><option>Other</option></select>
          </div>
          <div class="field">
            <label class="label">Expected monthly transactions</label>
            <select v-model="form.expected_txns"><option>1 – 5</option><option>6 – 20</option><option>21 – 50</option><option>50+</option></select>
          </div>
        </div>
      </div>

      <!-- 5. Account Type -->
      <div class="section">
        <div class="section-hdr">
          <div class="section-icon"><i class="ti ti-credit-card" aria-hidden="true"></i></div>
          <span class="section-title">Account type selection</span>
          <span class="section-num">05</span>
        </div>
        <div class="acc-grid">
          <div class="acc-card" :class="{'sel': form.account_type === 'Saving account'}" @click="selectAccount('Saving account')">
            <div class="acc-check"><i class="ti ti-check" aria-hidden="true"></i></div>
            <div class="acc-name">Saving account</div>
            <div class="acc-desc">For individuals saving money</div>
            <div class="acc-rate">3.5% annual interest</div>
          </div>
          <div class="acc-card" :class="{'sel': form.account_type === 'Current account'}" @click="selectAccount('Current account')">
            <div class="acc-check"><i class="ti ti-check" aria-hidden="true"></i></div>
            <div class="acc-name">Current account</div>
            <div class="acc-desc">For daily business use</div>
            <div class="acc-rate">No interest · overdraft allowed</div>
          </div>
          <div class="acc-card" :class="{'sel': form.account_type === 'Kaftoon account'}" @click="selectAccount('Kaftoon account')">
            <div class="acc-check"><i class="ti ti-check" aria-hidden="true"></i></div>
            <div class="acc-name">Kaftoon account</div>
            <div class="acc-desc">Islamic-compliant savings</div>
            <div class="acc-rate">Profit-sharing model</div>
          </div>
        </div>
        <div class="grid g2">
          <div class="field">
            <label class="label">Home branch<span class="req">*</span></label>
            <select v-model="form.home_branch" required><option value="">Select branch</option><option>Mogadishu — Main Branch</option><option>Garoowe — Nugaal Branch</option><option>Bosaso — Bari Branch</option></select>
          </div>
          <div class="field">
            <label class="label">Initial deposit amount</label>
            <input type="number" v-model="form.initial_deposit" placeholder="0.00" />
            <span class="hint">Minimum initial deposit applies per account type</span>
          </div>
        </div>
      </div>

      <!-- 6. KYC -->
      <div class="section">
        <div class="section-hdr">
          <div class="section-icon"><i class="ti ti-file-check" aria-hidden="true"></i></div>
          <span class="section-title">KYC documents required</span>
          <span class="section-num">06</span>
        </div>
        <div class="kyc-grid">
          <div class="kyc-item">
            <i class="ti ti-id" aria-hidden="true"></i>
            <span>National ID (front &amp; back)</span>
            <span class="kyc-status status-pending">Pending</span>
          </div>
          <div class="kyc-item">
            <i class="ti ti-camera" aria-hidden="true"></i>
            <span>Passport photo (recent)</span>
            <span class="kyc-status status-pending">Pending</span>
          </div>
          <div class="kyc-item">
            <i class="ti ti-home" aria-hidden="true"></i>
            <span>Proof of address</span>
            <span class="kyc-status status-pending">Pending</span>
          </div>
          <div class="kyc-item">
            <i class="ti ti-briefcase" aria-hidden="true"></i>
            <span>Employment letter / Business licence</span>
            <span class="kyc-status status-optional">Optional</span>
          </div>
        </div>
        <p class="hint" style="margin-top:10px; line-height:1.6">Documents are uploaded in the Staff Portal after initial registration entry.</p>
      </div>

      <!-- 7. Next of Kin -->
      <div class="section">
        <div class="section-hdr">
          <div class="section-icon"><i class="ti ti-users" aria-hidden="true"></i></div>
          <span class="section-title">Next of kin</span>
          <span class="section-num">07</span>
        </div>
        <div class="grid g3">
          <div class="field">
            <label class="label">Full name<span class="req">*</span></label>
            <input type="text" v-model="form.kin_name" placeholder="Next of kin name" required />
          </div>
          <div class="field">
            <label class="label">Relationship<span class="req">*</span></label>
            <select v-model="form.kin_relation" required><option value="">Select</option><option>Spouse</option><option>Parent</option><option>Sibling</option><option>Child</option><option>Other</option></select>
          </div>
          <div class="field">
            <label class="label">Phone number<span class="req">*</span></label>
            <input type="tel" v-model="form.kin_phone" placeholder="+252 61 000 0000" required />
          </div>
        </div>
      </div>

      <!-- 8. Declaration -->
      <div class="section">
        <div class="section-hdr">
          <div class="section-icon"><i class="ti ti-pen" aria-hidden="true"></i></div>
          <span class="section-title">Declaration &amp; signature</span>
          <span class="section-num">08</span>
        </div>
        <div class="declaration-box">
          I, the undersigned, declare that the information provided in this form is true, accurate, and complete to the best of my knowledge. I agree to notify the bank of any changes to my details. I consent to the bank processing my data for account management, compliance, and regulatory purposes. I acknowledge that providing false information may result in account suspension and legal action.
        </div>
        <label class="chk-row"><input type="checkbox" v-model="form.agreed_terms" required /> <span>I have read and agree to the Terms &amp; Conditions and Privacy Policy</span></label>
        <label class="chk-row"><input type="checkbox" v-model="form.agreed_notifications" /> <span>I consent to receiving account notifications via SMS and/or email</span></label>
        <label class="chk-row"><input type="checkbox" v-model="form.agreed_accurate" required /> <span>I confirm all information provided is accurate and complete</span></label>
        
        <div class="btn-row">
          <button type="button" class="btn-ghost" @click="form.reset()">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="form.processing">
            <i class="ti ti-device-floppy" aria-hidden="true"></i> Guard Record
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css');

:root {
  --navy: #0B1929;
  --navy-mid: #112236;
  --navy-surface: #162d47;
  --navy-border: rgba(255,255,255,0.08);
  --navy-border-strong: rgba(255,255,255,0.15);
  --gold: #C9A84C;
  --gold-light: #E5C97E;
  --gold-dim: rgba(201,168,76,0.18);
  --gold-glow: rgba(201,168,76,0.08);
  --text-primary: #F0EBE1;
  --text-secondary: #A9B8C6;
  --text-muted: #6B7E8E;
  --danger: #E2635A;
  --success: #4CAF7D;
  --warning: #E8A830;
  --input-bg: rgba(255,255,255,0.04);
  --input-border: rgba(255,255,255,0.1);
  --input-focus: rgba(201,168,76,0.45);
  --radius: 8px;
  --radius-lg: 14px;
}

:global(body) {
  font-family: 'DM Sans', sans-serif;
  background: #0B1929;
  color: #F0EBE1;
  min-height: 100vh;
}

.shell {
  max-width: 820px;
  margin: 0 auto;
  --navy: #0B1929;
  --navy-mid: #112236;
  --navy-surface: #162d47;
  --navy-border: rgba(255,255,255,0.08);
  --navy-border-strong: rgba(255,255,255,0.15);
  --gold: #C9A84C;
  --gold-light: #E5C97E;
  --gold-dim: rgba(201,168,76,0.18);
  --gold-glow: rgba(201,168,76,0.08);
  --text-primary: #F0EBE1;
  --text-secondary: #A9B8C6;
  --text-muted: #6B7E8E;
  --danger: #E2635A;
  --success: #4CAF7D;
  --warning: #E8A830;
  --input-bg: rgba(255,255,255,0.04);
  --input-border: rgba(255,255,255,0.1);
  --input-focus: rgba(201,168,76,0.45);
  --radius: 8px;
  --radius-lg: 14px;
}

.hdr { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 2.5rem; gap: 1rem; }
.bank-mark { display: flex; align-items: center; gap: 10px; margin-bottom: 1.2rem; }
.bank-logo { width: 36px; height: 36px; border-radius: 8px; background: var(--gold); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--navy); }
.bank-id { font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: var(--gold); font-weight: 500; }
.form-heading { font-family: 'DM Serif Display', serif; font-size: 32px; line-height: 1.15; color: var(--text-primary); margin-bottom: 6px; }
.form-heading em { font-style: italic; color: var(--gold-light); }
.form-sub { font-size: 13px; color: var(--text-secondary); max-width: 440px; line-height: 1.6; }
.hdr-right { text-align: right; flex-shrink: 0; }
.ref-tag { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; letter-spacing: .06em; color: var(--text-muted); border: 0.5px solid var(--navy-border-strong); border-radius: 20px; padding: 5px 12px; margin-bottom: 14px; }
.ref-tag i { font-size: 13px; color: var(--gold); }
.progress-wrap { display: flex; align-items: center; gap: 4px; }
.prog-step { width: 32px; height: 3px; border-radius: 2px; background: rgba(255,255,255,0.1); }
.prog-step.done { background: var(--gold); }
.prog-step.active { background: var(--gold); opacity: .45; }
.hdr-divider { height: 0.5px; background: linear-gradient(to right, var(--gold-dim), transparent); margin-bottom: 2.5rem; }

.section { margin-bottom: 2.5rem; animation: fadeUp .4s ease both; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.section:nth-child(1) { animation-delay: .05s; }
.section:nth-child(2) { animation-delay: .10s; }
.section:nth-child(3) { animation-delay: .15s; }
.section:nth-child(4) { animation-delay: .20s; }
.section:nth-child(5) { animation-delay: .25s; }
.section:nth-child(6) { animation-delay: .30s; }
.section:nth-child(7) { animation-delay: .35s; }
.section:nth-child(8) { animation-delay: .40s; }

.section-hdr { display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem; padding-bottom: .75rem; border-bottom: 0.5px solid var(--navy-border); }
.section-icon { width: 28px; height: 28px; border-radius: 7px; background: var(--gold-dim); display: flex; align-items: center; justify-content: center; font-size: 15px; color: var(--gold); flex-shrink: 0; }
.section-title { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--gold); font-weight: 500; }
.section-num { margin-left: auto; font-size: 11px; color: var(--text-muted); font-weight: 300; }

.grid { display: grid; gap: 14px; }
.g2 { grid-template-columns: 1fr 1fr; }
.g3 { grid-template-columns: 1fr 1fr 1fr; }
.mt { margin-top: 14px; }

.field { display: flex; flex-direction: column; gap: 5px; }
.label { font-size: 11.5px; color: var(--text-secondary); font-weight: 500; letter-spacing: .03em; }
.req { color: var(--gold); margin-left: 2px; }
.hint { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

input[type="text"], input[type="tel"], input[type="email"], input[type="number"], input[type="date"], select, textarea {
  width: 100%; font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--text-primary);
  background: var(--input-bg); border: 0.5px solid var(--input-border); border-radius: var(--radius);
  padding: 9px 12px; outline: none; transition: border-color .2s, box-shadow .2s; -webkit-appearance: none; appearance: none;
}
input::placeholder, textarea::placeholder { color: var(--text-muted); }
select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7E8E' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 28px; }
select option { background: #112236; color: var(--text-primary); }
input:focus, select:focus, textarea:focus { border-color: var(--gold); box-shadow: 0 0 0 3px var(--input-focus); }

.acc-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px; }
.acc-card { border: 0.5px solid var(--navy-border-strong); border-radius: var(--radius-lg); padding: 14px; cursor: pointer; transition: border-color .2s, background .2s; position: relative; overflow: hidden; }
.acc-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--gold); opacity: 0; transition: opacity .2s; }
.acc-card.sel { border-color: var(--gold); background: var(--gold-glow); }
.acc-card.sel::before { opacity: 1; }
.acc-card:hover:not(.sel) { border-color: rgba(201,168,76,0.35); background: rgba(255,255,255,0.02); }
.acc-name { font-size: 13px; font-weight: 500; color: var(--text-primary); margin-bottom: 4px; }
.acc-desc { font-size: 11px; color: var(--text-secondary); margin-bottom: 6px; line-height: 1.5; }
.acc-rate { font-size: 11px; color: var(--gold); font-weight: 500; }
.acc-check { position: absolute; top: 10px; right: 10px; width: 18px; height: 18px; border-radius: 50%; border: 1px solid var(--navy-border-strong); background: transparent; display: flex; align-items: center; justify-content: center; font-size: 11px; color: transparent; transition: all .2s; }
.acc-card.sel .acc-check { background: var(--gold); border-color: var(--gold); color: var(--navy); }

.kyc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.kyc-item { border: 0.5px solid var(--navy-border); border-radius: var(--radius); padding: 10px 12px; display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--text-secondary); background: var(--input-bg); cursor: pointer; transition: border-color .2s, background .2s; }
.kyc-item:hover { border-color: var(--navy-border-strong); background: rgba(255,255,255,0.04); }
.kyc-item i { font-size: 17px; color: var(--text-muted); flex-shrink: 0; }
.kyc-status { margin-left: auto; font-size: 10.5px; padding: 2px 9px; border-radius: 20px; flex-shrink: 0; }
.status-pending { background: rgba(232,168,48,.12); color: var(--warning); border: 0.5px solid rgba(232,168,48,.25); }
.status-optional { background: rgba(107,126,142,.12); color: var(--text-muted); border: 0.5px solid rgba(107,126,142,.25); }

.declaration-box { border: 0.5px solid var(--navy-border); border-left: 2px solid var(--gold); border-radius: 0 var(--radius) var(--radius) 0; padding: 14px 16px; font-size: 12.5px; color: var(--text-secondary); line-height: 1.75; background: rgba(201,168,76,0.04); margin-bottom: 1rem; }
.chk-row { display: flex; align-items: flex-start; gap: 10px; font-size: 12.5px; color: var(--text-secondary); margin-bottom: 10px; cursor: pointer; }
.chk-row input[type="checkbox"] { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; border: 1px solid var(--navy-border-strong); border-radius: 4px; background: transparent; accent-color: var(--gold); cursor: pointer; }

.btn-row { display: flex; gap: 12px; padding-top: 1.5rem; border-top: 0.5px solid var(--navy-border); margin-top: 2rem; }
.btn-ghost { background: transparent; color: var(--text-secondary); border: 0.5px solid var(--navy-border-strong); border-radius: var(--radius); padding: 11px 24px; font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer; transition: border-color .2s, color .2s, background .2s; }
.btn-ghost:hover { border-color: var(--text-muted); color: var(--text-primary); background: rgba(255,255,255,0.04); }
.btn-primary { flex: 1; background: var(--gold); color: var(--navy); border: none; border-radius: var(--radius); padding: 11px 24px; font-family: 'DM Serif Display', serif; font-size: 15px; font-weight: 400; cursor: pointer; letter-spacing: .02em; transition: background .2s, transform .1s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-primary:hover { background: var(--gold-light); }
.btn-primary:active { transform: scale(0.99); }
.btn-primary i { font-size: 16px; }

@media (max-width: 600px) {
  .g3 { grid-template-columns: 1fr 1fr; }
  .acc-grid { grid-template-columns: 1fr; }
  .kyc-grid { grid-template-columns: 1fr; }
  .hdr { flex-direction: column; }
  .hdr-right { text-align: left; }
  .form-heading { font-size: 26px; }
}
@media (max-width: 400px) {
  .g2, .g3 { grid-template-columns: 1fr; }
}
</style>
