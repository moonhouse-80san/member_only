<?php

namespace Rhymix\Modules\Member_Only\Controllers;

use Rhymix\Framework\Session;
use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Exceptions\NotPermitted;
use Rhymix\Modules\Member_Only\Models\Config;

/**
 * MemberOnly admin controller.
 */
class Admin extends Base {

  /**
   * 설정 화면. 선택 상태가 반영된 게시판 목록(설정된 순서로 정렬)과 회원그룹 목록을 함께 넘긴다.
   */
  public function dispMember_onlyAdminConfig() {
    $config = Config::getConfig();
    $boards = array_filter(\ModuleModel::getMidList() ?: [], fn($item) => $item->module === 'board');

    $board_rows = [];
    foreach ($boards as $board) {
      $row = new \stdClass;
      $row->module_srl = (int)$board->module_srl;
      $row->mid = (string)$board->mid;
      // 저장된 제목의 HTML 엔티티(&quot; 등)는 풀어서 넘긴다. (템플릿에서 다시 이스케이프됨)
      $title = trim(html_entity_decode((string)($board->browser_title ?? ''), ENT_QUOTES, 'UTF-8'));
      $row->title = $title !== '' ? $title : $row->mid;
      $row->checked = in_array($row->module_srl, $config->module_srls, true);
      $board_rows[] = $row;
    }

    $sort = $config->board_sort;
    usort($board_rows, function ($a, $b) use ($sort) {
      if ($sort === 'mid') return strcasecmp($a->mid, $b->mid);
      if ($sort === 'created') return $a->module_srl - $b->module_srl;
      return strcasecmp($a->title, $b->title);
    });

    $group_rows = [];
    foreach (\MemberModel::getGroups() ?: [] as $group) {
      $row = new \stdClass;
      $row->group_srl = (int)$group->group_srl;
      $row->title = trim(html_entity_decode((string)$group->title, ENT_QUOTES, 'UTF-8'));
      $row->is_admin = ($group->is_admin ?? '') === 'Y';
      $row->is_default = ($group->is_default ?? '') === 'Y';
      $row->checked = in_array($row->group_srl, $config->group_srls, true);
      $group_rows[] = $row;
    }

    \Context::set('config', $config);
    \Context::set('password_is_default', !$config->password_hash);
    \Context::set('board_rows', $board_rows);
    \Context::set('group_rows', $group_rows);

    $this->setTemplatePath($this->module_path . 'views');
    $this->setTemplateFile('config.blade.php');
  }

  /**
   * 설정 저장.
   */
  public function procMember_onlyAdminInsertConfig() {
    if (!Session::isAdmin()) {
      throw new NotPermitted;
    }

    $vars = \Context::getRequestVars();
    $eid = trim($vars->target_eid ?? '');
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $eid)) {
      throw new InvalidRequest('확장변수 식별자는 영문으로 시작하는 영문/숫자/밑줄만 사용할 수 있습니다.');
    }

    $config = Config::getConfig();
    $config->eid = $eid;
    $config->list_mode = ($vars->list_mode ?? '') === 'mask' ? 'mask' : 'hide';
    $config->use_password = ($vars->use_password ?? '') === 'Y' ? 'Y' : 'N';

    // 열람 비밀번호: 입력했을 때만 변경(해시로 저장), '기본값으로 되돌리기'를 체크하면 기본 비밀번호(00000000)로 복귀
    $password = (string)($vars->view_password ?? '');
    if (($vars->reset_password ?? '') === 'Y') {
      $config->password_hash = '';
    } elseif ($password !== '') {
      if (mb_strlen($password) < 4 || mb_strlen($password) > 64) {
        throw new InvalidRequest('열람 비밀번호는 4자 이상 64자 이하로 입력해 주세요.');
      }
      $config->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    $config->board_sort = in_array($vars->board_sort ?? '', ['title', 'mid', 'created'], true) ? $vars->board_sort : 'title';
    $config->module_srls = array_values(array_filter(array_map('intval', (array)($vars->module_srls ?? []))));
    $config->group_srls = array_values(array_filter(array_map('intval', (array)($vars->group_srls ?? []))));

    $output = \ModuleController::getInstance()->insertModuleConfig('member_only', $config);
    if (!$output->toBool()) {
      return $output;
    }

    $this->setMessage('success_updated');
    $this->setRedirectUrl(\Context::get('success_return_url') ?: getNotEncodedUrl([
      'module' => 'admin',
      'act' => 'dispMember_onlyAdminConfig',
    ]));
  }
}
