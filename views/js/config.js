/**
 * member_only 관리자 설정 화면
 * - 게시판/회원그룹 카드 선택 (전체 선택·해제, 선택 개수, 선택 상태 표시)
 * - 게시판 정렬 기준(제목순·이름순·생성순) 변경 시 즉시 재정렬
 */
(function () {
  function initPicker(box) {
    var wrap    = box.querySelector('.mo-modules');
    if (!wrap) return;
    var allBtn  = box.querySelector('.mo-modules-all');
    var countEl = box.querySelector('.mo-modcount-num');
    var sortSel = box.querySelector('.mo-modsort-select');

    function boxes() { return wrap.querySelectorAll('input[type="checkbox"]'); }

    function refresh() {
      var list = boxes(), total = list.length, checked = 0;
      for (var i = 0; i < total; i++) {
        var on = list[i].checked;
        if (on) checked++;
        var item = list[i].closest('.mo-module-item');
        if (item) item.classList.toggle('mo-is-checked', on);
      }
      if (countEl) countEl.textContent = checked;
      if (allBtn) {
        var allOn = (total > 0 && checked === total);
        allBtn.textContent = allOn ? '전체 해제' : '전체 선택';
        allBtn.classList.toggle('mo-is-active', allOn);
      }
    }

    if (allBtn) {
      allBtn.addEventListener('click', function () {
        var list = boxes(), total = list.length, checked = 0;
        for (var i = 0; i < total; i++) { if (list[i].checked) checked++; }
        var turnOn = !(total > 0 && checked === total); // 전부 선택돼 있으면 해제, 아니면 전체 선택
        for (var j = 0; j < total; j++) list[j].checked = turnOn;
        refresh();
      });
    }

    if (sortSel) {
      sortSel.addEventListener('change', function () {
        var key   = sortSel.value;
        var attr  = (key === 'mid') ? 'data-mid' : 'data-title';
        var items = Array.prototype.slice.call(wrap.querySelectorAll('.mo-module-item'));
        items.sort(function (a, b) {
          if (key === 'created') return (parseInt(a.getAttribute('data-srl'), 10) || 0) - (parseInt(b.getAttribute('data-srl'), 10) || 0);
          var av = (a.getAttribute(attr) || '').toLowerCase();
          var bv = (b.getAttribute(attr) || '').toLowerCase();
          return av < bv ? -1 : (av > bv ? 1 : 0);
        });
        for (var k = 0; k < items.length; k++) wrap.appendChild(items[k]);
      });
    }

    wrap.addEventListener('change', function (e) {
      if (e.target && e.target.type === 'checkbox') refresh();
    });

    refresh();
  }

  // '제목만 가리기'를 고르면 열람 방법 줄을, 거기서 '로그인 또는 비밀번호'를 고르면 비밀번호 줄을 보여준다.
  function initAccessRows() {
    var accessRow = document.querySelector('.mo-admin .mo-access-row');
    var pwRow     = document.querySelector('.mo-admin .mo-pw-row');
    var modeRadios = document.querySelectorAll('.mo-admin input[name="list_mode"]');
    var pwRadios   = document.querySelectorAll('.mo-admin input[name="use_password"]');
    if (!accessRow || !pwRow || !modeRadios.length || !pwRadios.length) return;

    function checkedValue(list) {
      for (var i = 0; i < list.length; i++) {
        if (list[i].checked) return list[i].value;
      }
      return '';
    }
    function sync() {
      var mask = (checkedValue(modeRadios) === 'mask');
      accessRow.style.display = mask ? '' : 'none';
      pwRow.style.display = (mask && checkedValue(pwRadios) === 'Y') ? '' : 'none';
    }
    var all = Array.prototype.slice.call(modeRadios).concat(Array.prototype.slice.call(pwRadios));
    for (var j = 0; j < all.length; j++) all[j].addEventListener('change', sync);
    sync();
  }

  function init() {
    initAccessRows();
    var pickers = document.querySelectorAll('.mo-admin [data-mo-picker]');
    for (var i = 0; i < pickers.length; i++) initPicker(pickers[i]);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
