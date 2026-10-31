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
    if (self::isUnlocked($config)) {
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
   * 비밀번호 열람을 쓰는지 확인한다. '제목만 가리기' 방식에서 '로그인 또는 비밀번호'를 선택했을 때만 해당한다.
   *
   * @param object $config
   * @return bool
   */
  public static function usePassword($config) {
    return $config->use_password === 'Y' && $config->list_mode === 'mask';
  }

  /**
   * 열람 비밀번호의 현재 상태 식별값. 비밀번호가 바뀌면 값이 달라져 기존 열람 허가가 무효가 된다.
   *
   * @param object $config
   * @return string
   */
  public static function getUnlockToken($config) {
    return hash('sha256', 'member_only|' . ($config->password_hash ?: 'default'));
  }

  /**
   * 비밀번호로 열람 허가를 받은 방문자인지 확인한다. (설정: 로그인 또는 비밀번호)
   *
   * @param object $config
   * @return bool
   */
  public static function isUnlocked($config) {
    if (!self::usePassword($config)) {
      return false;
    }
    $token = Session::get('member_only_unlock');
    return is_string($token) && hash_equals(self::getUnlockToken($config), $token);
  }

  /**
   * 입력한 비밀번호가 맞는지 확인한다. 별도로 지정하지 않았다면 기본 비밀번호(00000000)와 비교한다.
   *
   * @param object $config
   * @param string $input
   * @return bool
   */
  public static function checkPassword($config, $input) {
    $input = (string)$input;
    if ($config->password_hash) {
      return password_verify($input, $config->password_hash);
    }
    return hash_equals('00000000', $input);
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
