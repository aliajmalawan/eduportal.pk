<div class="modal-overlay" id="getStartedModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true">
  <div class="modal">
    <h2 id="modalTitle">Get Started with EduPortal</h2>
    <p class="modal-desc">Share a few details about your institute and we'll set up a quick, personalized demo for you.</p>
    <form id="getStartedForm" novalidate>
      <div class="form-row">
        <div class="form-group form-group--grow">
          <label for="instituteName">Institute Name</label>
          <input type="text" id="instituteName" name="instituteName" placeholder="Institute name" required>
        </div>
        <div class="form-group form-group--side">
          <label for="studentCount">Strength</label>
          <input type="text" id="studentCount" name="studentCount" placeholder="e.g. 500" inputmode="numeric">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group form-group--grow">
          <label for="fullName">Contact Person Name</label>
          <input type="text" id="fullName" name="fullName" placeholder="Full name" required>
        </div>
        <div class="form-group form-group--side">
          <label for="designation">Designation / Role</label>
          <input type="text" id="designation" name="designation" placeholder="Role" required>
        </div>
      </div>
      <div class="form-group">
        <label for="whatsapp">WhatsApp Number</label>
        <div class="phone-input">
          <select id="countryCode" name="country_code" class="phone-country-select" aria-label="Country code"></select>
          <input type="tel" id="whatsapp" name="whatsapp" placeholder="300 1234567" inputmode="tel" autocomplete="tel-national" required>
        </div>
        <input type="hidden" id="whatsappFull" name="whatsapp_full" value="">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" id="modalCancel">Cancel</button>
        <button type="submit" class="btn-modal-submit">Book now</button>
      </div>
    </form>
  </div>
</div>
