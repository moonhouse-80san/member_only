<div class="x_page-header">
  <h1>회원 전용 게시글</h1>
</div>

<form action="./" class="x_form-horizontal" method="post">
  <input type="hidden" name="module" value="member_only" />
  <input type="hidden" name="act" value="procMember_onlyAdminInsertConfig" />
  <input type="hidden" name="success_return_url" value="{{ getUrl(['module' => 'admin', 'act' => $act]) }}" />

  <div class="x_control-group">
    <label class="x_control-label" for="target_eid">확장변수 식별자(eid)</label>
    <div class="x_controls">
      <input type="text" id="target_eid" name="target_eid" value="{{ $config->eid }}" />
      <p class="x_help-block">이 식별자의 확장변수 값에 Y가 포함된 글이 회원 전용 글입니다. (체크박스/선택 유형, 값 Y)</p>
    </div>
  </div>

  <div class="x_control-group">
    <label class="x_control-label">목록 표시 방식</label>
    <div class="x_controls">
      <label class="x_inline"><input type="radio" name="list_mode" value="hide" @checked($config->list_mode === 'hide') /> 목록에서 숨기기</label>
      <label class="x_inline"><input type="radio" name="list_mode" value="mask" @checked($config->list_mode === 'mask') /> 제목만 가리기 ("회원 전용 글입니다.")</label>
      <p class="x_help-block">열람 권한이 없는 방문자에게 적용됩니다. 숨기기를 쓰면 페이지마다 보이는 글 수가 줄어들 수 있습니다.</p>
    </div>
  </div>

  <div class="x_control-group">
    <label class="x_control-label">적용할 게시판</label>
    <div class="x_controls">
      <p class="x_help-block mup">선택한 게시판에만 적용됩니다. 아무것도 선택하지 않으면 모든 게시판에 적용됩니다. (확장변수가 지정된 글만 대상)</p>
      <div style="display:flex;flex-wrap:wrap;gap:6px 20px;margin-bottom:6px">
      @foreach ($board_list as $board)
        <label class="x_inline">
          <input type="checkbox" name="module_srls[]" value="{{ $board->module_srl }}" @checked(in_array((int)$board->module_srl, $config->module_srls, true)) />
          {{ $board->browser_title }} ({{ $board->mid }})
        </label>
      @endforeach
      </div>
    </div>
  </div>

  <div class="x_control-group">
    <label class="x_control-label">열람 가능한 회원그룹</label>
    <div class="x_controls">
      <p class="x_help-block mup">선택하지 않으면 로그인한 회원 모두가 볼 수 있습니다. 그룹을 선택하면 해당 그룹의 회원만 볼 수 있습니다. 관리자는 항상 볼 수 있습니다.</p>
      <div style="display:flex;flex-wrap:wrap;gap:6px 20px;margin-bottom:6px">
      @foreach ($group_list as $group)
        <label class="x_inline">
          <input type="checkbox" name="group_srls[]" value="{{ $group->group_srl }}" @checked(in_array((int)$group->group_srl, $config->group_srls, true)) />
          {{ $group->title }}
        </label>
      @endforeach
      </div>
    </div>
  </div>

  <div class="x_clearfix btnArea">
    <span class="x_pull-right">
      <button type="submit" class="x_btn x_btn-primary">{{ $lang->cmd_save }}</button>
    </span>
  </div>
</form>

<style type="text/css">
	.x_help-block { font-size:14px !important; }
	.mup { margin:4px 0 10px 0 !important; }
</style>
