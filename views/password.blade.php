@load('css/password.css')

<div class="mo-pw">
  <div class="mo-pw-card">
    <h2 class="mo-pw-title">회원 전용 글입니다</h2>
    <p class="mo-pw-desc">로그인하거나 비밀번호를 입력하면 이 글을 볼 수 있습니다.</p>

    <form action="./" method="post" class="mo-pw-form">
      <input type="hidden" name="module" value="member_only" />
      <input type="hidden" name="act" value="procMember_onlyUnlock" />
      <input type="hidden" name="mo_mid" value="{{ $mo_mid }}" />
      <input type="hidden" name="mo_srl" value="{{ $mo_srl }}" />
      <input type="hidden" name="error_return_url" value="{{ $pw_url }}" />
      <input type="password" name="password" class="mo-pw-input" placeholder="비밀번호" autocomplete="off" required />
      <button type="submit" class="mo-pw-btn mo-pw-btn-primary">확인</button>
    </form>

    @if (!$is_member)
    <p class="mo-pw-or">또는</p>
    <a href="{{ $login_url }}" class="mo-pw-btn mo-pw-btn-ghost">로그인</a>
    @endif
  </div>
</div>
