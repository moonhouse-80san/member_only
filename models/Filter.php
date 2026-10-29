<?php

namespace Rhymix\Modules\Member_Only\Models;

use Rhymix\Framework\Session;

/**
 * MemberOnly filter model. 목록/위젯/열람 처리에서 공통으로 사용한다.
 */
class Filter {

  /**
   * 대상 게시판의 회원 전용 글인지 확인한다.
   *
   * @param \DocumentItem $oDocument
   * @param object $config
   * @return bool
   */
  public static function isMemberOnly($oDocument, $config) {
    return (!$config->module_srls || in_array((int)$oDocument->get('module_srl'), $config->module_srls, true))
      && in_array('Y', (array)$oDocument->getExtraEidValue($config->eid), true);
  }

  /**
   * 현재 방문자가 회원 전용 글을 볼 수 있는지 확인한다.
   * 관리자는 항상 가능, 허용 그룹을 지정하지 않으면 로그인 회원 모두 가능.
   *
   * @param object $config
   * @return bool
   */
  public static function canView($config) {
    if (Session::isAdmin()) {
      return true;
    }
    if (!Session::isMember()) {
      return false;
    }
    if (!$config->group_srls) {
      return true;
    }
    return (bool)array_intersect($config->group_srls, array_keys(Session::getMemberInfo()->group_list ?: []));
  }

  /**
   * 현재 방문자에게 숨겨야 할 회원 전용 글의 document_srl 목록을 반환한다. (위젯 쿼리 제외용)
   *
   * @param array $moduleSrls 위젯 대상 module_srl 목록. 비어 있으면 설정된 대상 게시판(설정도 비어 있으면 전체).
   * @return int[]
   */
  public static function getHiddenSrls($moduleSrls = []) {
    $config = Config::getConfig();
    if (self::canView($config)) {
      return [];
    }

    $moduleSrls = array_filter(array_map('intval', $moduleSrls));
    if ($moduleSrls && $config->module_srls) {
      $moduleSrls = array_intersect($moduleSrls, $config->module_srls);
      if (!$moduleSrls) {
        return [];
      }
    }

    $args = new \stdClass;
    $args->module_srl = implode(',', $moduleSrls ?: $config->module_srls) ?: null;
    $args->eid = $config->eid;
    $args->var_value = 'Y';
    $output = executeQueryArray('member_only.getMemberOnlyDocumentSrls', $args);
    return $output->toBool() ? array_map('intval', array_column($output->data ?: [], 'document_srl')) : [];
  }
}
