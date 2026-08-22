/**
 * assets/js/script.js
 * ---------------------------------------------------------------------
 * JS thuần, không phụ thuộc thư viện ngoài. Chỉ tăng cường trải nghiệm
 * (progressive enhancement) — mọi kiểm tra dữ liệu quan trọng vẫn được
 * xử lý lại ở phía server (PHP), JS ở đây KHÔNG phải là lớp bảo mật.
 * ---------------------------------------------------------------------
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initNavToggle();
    initPasswordToggles();
    initPasswordStrengthMeter();
    initPasswordMatchCheck();
    preventDoubleSubmit();
    initAppSidebarToggle();
  });

  /** Menu điều hướng thu gọn trên màn hình nhỏ. */
  function initNavToggle() {
    var toggle = document.getElementById('navToggle');
    var links = document.getElementById('navLinks');
    if (!toggle || !links) return;

    toggle.addEventListener('click', function () {
      var isOpen = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }

  /** Nút hiện/ẩn mật khẩu (👁) cho mọi input có data-target trỏ tới id ô mật khẩu. */
  function initPasswordToggles() {
    var buttons = document.querySelectorAll('.password-toggle');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var targetId = btn.getAttribute('data-target');
        var input = targetId ? document.getElementById(targetId) : null;
        if (!input) return;
        var showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.setAttribute('aria-pressed', showing ? 'false' : 'true');
        btn.textContent = showing ? '👁' : '🙈';
      });
    });
  }

  /** Thanh đo độ mạnh mật khẩu (chỉ mang tính gợi ý trực quan). */
  function initPasswordStrengthMeter() {
    var passwordInput = document.getElementById('mat_khau');
    var meter = document.getElementById('strengthMeter');
    if (!passwordInput || !meter) return;
    var bar = meter.querySelector('span');

    passwordInput.addEventListener('input', function () {
      var value = passwordInput.value;
      var score = scorePassword(value);
      var percent = Math.min(100, score * 25);
      var color = '#dc2626';
      if (score >= 3) color = '#16a34a';
      else if (score === 2) color = '#d97706';

      bar.style.width = value.length === 0 ? '0%' : percent + '%';
      bar.style.background = color;
    });
  }

  function scorePassword(value) {
    var score = 0;
    if (value.length >= 8) score++;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
    if (/\d/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;
    return score;
  }

  /** Kiểm tra realtime "Xác nhận mật khẩu" có khớp không (chỉ ở form đăng ký). */
  function initPasswordMatchCheck() {
    var pass = document.getElementById('mat_khau');
    var confirm = document.getElementById('xac_nhan_mat_khau');
    var errorEl = document.getElementById('matchError');
    if (!pass || !confirm || !errorEl) return;

    function check() {
      if (confirm.value.length === 0) {
        errorEl.hidden = true;
        return;
      }
      var mismatch = confirm.value !== pass.value;
      errorEl.hidden = !mismatch;
      confirm.classList.toggle('input-error', mismatch);
    }

    pass.addEventListener('input', check);
    confirm.addEventListener('input', check);
  }

  /**
   * Chống bấm submit nhiều lần liên tiếp (double submit) trên các form
   * quan trọng — vô hiệu hoá nút Submit ngay sau khi form được gửi.
   */
  function preventDoubleSubmit() {
    var forms = document.querySelectorAll('form');
    forms.forEach(function (form) {
      form.addEventListener('submit', function () {
        var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
          window.setTimeout(function () {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.dataset.originalText || submitBtn.textContent;
            submitBtn.textContent = 'Đang xử lý...';
          }, 0);
        }
      });
    });
  }
  /** Mở/đóng sidebar (khung app đã đăng nhập) trên màn hình nhỏ. */
  function initAppSidebarToggle() {
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('appSidebar');
    var scrim = document.getElementById('sidebarScrim');
    if (!toggle || !sidebar || !scrim) return;

    function close() {
      sidebar.classList.remove('open');
      scrim.classList.remove('open');
    }
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
      scrim.classList.toggle('open');
    });
    scrim.addEventListener('click', close);
  }
})();
