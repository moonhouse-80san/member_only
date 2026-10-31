<?php

namespace Rhymix\Modules\Member_Only\Models;

/**
 * MemberOnly configuration model.
 */
class Config {

  /**
   * 모듈 설정을 가져온다. (기본값 포함)
   *
   * @return object
   */
  public static function getConfig() {
    $config = \ModuleModel::getModuleConfig('member_only') ?: new \stdClass;
    $config->eid = trim($config->eid ?? '') ?: 'member_only';
    $config->list_mode = in_array($config->list_mode ?? '', ['hide', 'mask'], true) ? $config->list_mode : 'hide';
    $config->board_sort = in_array($config->board_sort ?? '', ['title', 'mid', 'created'], true) ? $config->board_sort : 'title';
    $config->use_password = ($config->use_password ?? '') === 'Y' ? 'Y' : 'N';
    $config->password_hash = (string)($config->password_hash ?? '');
    $config->module_srls = array_map('intval', (array)($config->module_srls ?? []));
    $config->group_srls = array_map('intval', (array)($config->group_srls ?? []));
    return $config;
  }
}
