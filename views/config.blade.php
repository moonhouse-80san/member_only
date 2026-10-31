@load('css/config.css')
@load('js/config.js', 'body')

<div class="mo-admin">
  <header class="mo-head">
    <h2>회원 전용 게시글</h2>
    <p class="mo-head-desc">확장변수가 체크된 게시글을 로그인 회원에게만 보여줍니다.</p>
  </header>

  <form action="./" method="post">
    <input type="hidden" name="module" value="member_only" />
    <input type="hidden" name="act" value="procMember_onlyAdminInsertConfig" />
    <input type="hidden" name="success_return_url" value="{{ getUrl(['module' => 'admin', 'act' => $act]) }}" />

    <!-- 모듈 설정 -->
    <section class="mo-card">
      <div class="mo-card-head">모듈 설정</div>
      <div class="mo-card-body">
        <div class="mo-form-rows">
          <div class="mo-form-row">
            <label class="mo-label" for="mo_target_eid">확장변수 식별자</label>
            <div class="mo-control mo-control-stack">
              <div class="mo-inputwrap mo-input-w">
                <input type="text" id="mo_target_eid" name="target_eid" value="{{ $config->eid }}" class="mo-input-flush" />
              </div>
              <span class="mo-hint">이 식별자의 확장변수 값에 <b>Y</b>가 포함된 글이 회원 전용 글입니다. (체크박스/선택 유형, 값 Y)</span>
            </div>
          </div>
          <div class="mo-form-row">
            <span class="mo-label">목록 표시 방식</span>
            <div class="mo-control mo-control-stack">
              <div class="mo-radios">
                <label class="mo-radio"><input type="radio" name="list_mode" value="hide" class="mo-radio-input" @checked($config->list_mode === 'hide') /> <span>목록에서 숨기기</span></label>
                <label class="mo-radio"><input type="radio" name="list_mode" value="mask" class="mo-radio-input" @checked($config->list_mode === 'mask') /> <span>제목만 가리기 ("회원 전용 글입니다.")</span></label>
              </div>
              <span class="mo-hint">열람 권한이 없는 방문자에게 적용됩니다. 숨기기를 쓰면 페이지마다 보이는 글 수가 줄어들 수 있습니다. <b>제목만 가리기</b>를 고르면 아래에서 열람 방법(로그인 또는 비밀번호)을 정할 수 있습니다.</span>
            </div>
          </div>
          <div class="mo-form-row mo-access-row" @style(['display:none' => $config->list_mode !== 'mask'])>
            <span class="mo-label">열람 방법</span>
            <div class="mo-control mo-control-stack">
              <div class="mo-radios">
                <label class="mo-radio"><input type="radio" name="use_password" value="N" class="mo-radio-input" @checked($config->use_password !== 'Y') /> <span>로그인 회원만</span></label>
                <label class="mo-radio"><input type="radio" name="use_password" value="Y" class="mo-radio-input" @checked($config->use_password === 'Y') /> <span>로그인 또는 비밀번호</span></label>
              </div>
              <span class="mo-hint">'로그인 또는 비밀번호'를 고르면 로그인하지 않은 방문자도 <b>비밀번호를 입력해 회원 전용 글을 볼 수 있습니다.</b> (브라우저를 닫을 때까지 유지)</span>
            </div>
          </div>
          <div class="mo-form-row mo-pw-row" @style(['display:none' => $config->list_mode !== 'mask' || $config->use_password !== 'Y'])>
            <label class="mo-label" for="mo_view_password">열람 비밀번호</label>
            <div class="mo-control mo-control-stack">
              <div class="mo-inputwrap mo-input-w">
                <input type="password" id="mo_view_password" name="view_password" class="mo-input-flush" autocomplete="new-password" placeholder="변경할 비밀번호 (비우면 유지)" />
              </div>
              @if ($password_is_default)
              <span class="mo-hint mo-hint-warn">현재 <b>기본 비밀번호(00000000)</b>를 사용 중입니다. 누구나 추측하기 쉬우니 변경하는 것을 권장합니다.</span>
              @else
              <span class="mo-hint">현재 <b>직접 지정한 비밀번호</b>를 사용 중입니다. (보안을 위해 저장된 비밀번호는 표시되지 않습니다)</span>
              <label class="mo-radio mo-radio-small"><input type="checkbox" name="reset_password" value="Y" class="mo-radio-input" /> <span>기본 비밀번호(00000000)로 되돌리기</span></label>
              @endif
              <span class="mo-hint">4자 이상 입력해 주세요. 비밀번호를 바꾸면 이미 열람 중인 방문자는 다시 입력해야 합니다.</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 적용할 게시판 -->
    <section class="mo-card" data-mo-picker>
      <div class="mo-card-head">
        적용할 게시판 <span class="mo-card-head-sub">회원 전용 처리를 적용할 게시판 선택</span>
      </div>
      <div class="mo-card-body">
        <p class="mo-note">선택한 게시판에만 적용됩니다. <b>아무것도 선택하지 않으면 모든 게시판에 적용</b>됩니다. (확장변수가 지정된 글만 대상)</p>

        @if (count($board_rows))
        <div class="mo-modtoolbar">
          <div class="mo-modtools">
            <button type="button" class="mo-btn mo-btn-ghost mo-modules-all">전체 선택</button>
            <select name="board_sort" class="mo-select mo-modsort-select" title="게시판 정렬 순서">
              <option value="title" @selected($config->board_sort === 'title')>제목순 (문하우스)</option>
              <option value="mid" @selected($config->board_sort === 'mid')>이름순 (home)</option>
              <option value="created" @selected($config->board_sort === 'created')>생성순</option>
            </select>
          </div>
          <span class="mo-modcount"><b class="mo-modcount-num">0</b>개 선택됨</span>
        </div>

        <div class="mo-modules">
          @foreach ($board_rows as $board)
          <label @class(['mo-module-item', 'mo-is-checked' => $board->checked]) data-title="{{ $board->title }}" data-mid="{{ $board->mid }}" data-srl="{{ $board->module_srl }}">
            <input type="checkbox" name="module_srls[]" value="{{ $board->module_srl }}" class="mo-module-input" @checked($board->checked) />
            <span class="mo-module-text">
              <span class="mo-module-name"><span>{{ $board->title }}</span></span>
              <span class="mo-module-meta">{{ $board->mid }} <span class="mo-module-sep">·</span> BOARD</span>
            </span>
          </label>
          @endforeach
        </div>
        @else
        <p class="mo-empty-modules">표시할 게시판이 없습니다.</p>
        @endif
      </div>
    </section>

    <!-- 열람 가능한 회원그룹 -->
    <section class="mo-card" data-mo-picker>
      <div class="mo-card-head">
        열람 가능한 회원그룹 <span class="mo-card-head-sub">회원 전용 글을 볼 수 있는 그룹 선택</span>
      </div>
      <div class="mo-card-body">
        <p class="mo-note">선택하지 않으면 <b>로그인한 회원 모두</b>가 볼 수 있습니다. 그룹을 선택하면 해당 그룹의 회원만 볼 수 있습니다. 관리자는 항상 볼 수 있습니다.</p>

        @if (count($group_rows))
        <div class="mo-modtoolbar">
          <div class="mo-modtools">
            <button type="button" class="mo-btn mo-btn-ghost mo-modules-all">전체 선택</button>
          </div>
          <span class="mo-modcount"><b class="mo-modcount-num">0</b>개 선택됨</span>
        </div>

        <div class="mo-modules">
          @foreach ($group_rows as $group)
          <label @class(['mo-module-item', 'mo-is-checked' => $group->checked])>
            <input type="checkbox" name="group_srls[]" value="{{ $group->group_srl }}" class="mo-module-input" @checked($group->checked) />
            <span class="mo-module-text">
              <span class="mo-module-name">
                <span>{{ $group->title }}</span>
                @if ($group->is_admin)<span class="mo-module-tag">관리자</span>@endif
                @if ($group->is_default)<span class="mo-module-tag mo-module-tag-muted">기본</span>@endif
              </span>
              <span class="mo-module-meta">group <span class="mo-module-sep">·</span> {{ $group->group_srl }}</span>
            </span>
          </label>
          @endforeach
        </div>
        @else
        <p class="mo-empty-modules">표시할 회원그룹이 없습니다.</p>
        @endif
      </div>
    </section>

    <div class="mo-savebar">
      <button type="submit" class="mo-btn mo-btn-primary">설정 저장</button>
    </div>
  </form>
</div>
