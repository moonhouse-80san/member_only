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
   * 설정 화면. 게시판 목록과 회원그룹 목록을 함께 넘긴다.
   */
  public function dispMember_onlyAdminConfig() {
    $boards = array_filter(\ModuleModel::getMidList() ?: [], fn($item) => $item->module === 'board');

    \Context::set('config', Config::getConfig());
    \Context::set('board_list', $boards);
    \Context::set('group_list', \MemberModel::getGroups());

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
