<?php

namespace Rhymix\Modules\Member_Only\Models;

use Rhymix\Framework\Session;

/**
 * MemberOnly filter model. 목록/위젯/열람 처리에서 공통으로 사용한다.
 */
class Filter {

  /** 포인트 모듈을 쓸 수 없을 때 로그인 회원에게 주는 기본 레벨 (비로그인은 0) */
  const DEFAULT_MEMBER_LEVEL = 1;

  /**
   * 대상 게시판의 회원 전용 글인지 확인한다.
   *
   * @param \DocumentItem $oDocument
   * @param object $config
   * @return bool
   */
  public static function isMemberOnly($oDocument, $config) {
    return self::inScope($oDocument, $config)
      && in_array('Y', (array)$oDocument->getExtraEidValue($config->eid), true);
  }

  /**
   * 설정한 대상 게시판의 글인지 확인한다. (대상 게시판을 비워 두면 모든 게시판)
   */
  private static function inScope($oDocument, $config) {
    return !$config->module_srls || in_array((int)$oDocument->get('module_srl'), $config->module_srls, true);
  }

  /**
   * 글에 지정된 열람 레벨을 반환한다. 확장변수(level_eid)가 비었거나 0이면 레벨 제한 없음.
   *
   * @param \DocumentItem $oDocument
   * @param object $config
   * @return int
   */
  public static function getRequiredLevel($oDocument, $config) {
    if (!self::inScope($oDocument, $config)) {
      return 0;
    }
    $value = $oDocument->getExtraEidValue($config->level_eid);
    if (is_array($value)) {
      $value = reset($value);
    }
    return max(0, (int)trim((string)$value));
  }

  /**
   * 현재 방문자의 레벨. 관리자는 최고, 비로그인은 0,
   * 회원은 회원 > 포인트 설정의 레벨 기준(포인트가 쌓일수록 상승)에 따른 현재 레벨.
   * 포인트 모듈 설정을 읽을 수 없으면 로그인 회원은 기본 레벨(1)로 본다.
   *
   * @param object $config
   * @return int
   */
  public static function getViewerLevel($config) {
    if (Session::isAdmin()) {
      return PHP_INT_MAX;
    }
    if (!Session::isMember()) {
      return 0;
    }
    $pointConfig = \ModuleModel::getModuleConfig('point');
    if (!class_exists('PointModel') || empty($pointConfig->level_step)) {
      return self::DEFAULT_MEMBER_LEVEL;
    }
    $memberSrl = (int)Session::getMemberSrl();
    return max(0, (int)\PointModel::getLevel(\PointModel::getPoint($memberSrl), $pointConfig->level_step));
  }

  /**
   * 검색봇 허용 설정이 켜져 있고 검증된 검색봇이면 레벨 제한도 적용하지 않는다.
   */
  public static function isLevelExempt($config) {
    return $config->allow_bots === 'Y' && BotVerify::isVerified();
  }

  /**
   * 레벨이 부족해서 볼 수 없는 글인지 확인한다.
   */
  public static function isLevelRestricted($oDocument, $config) {
    $required = self::getRequiredLevel($oDocument, $config);
    if ($required <= 0) {
      return false;
    }
    return self::getViewerLevel($config) < $required && !self::isLevelExempt($config);
  }

  /**
   * 현재 방문자가 이 글을 볼 수 없는 이유를 반환한다. 볼 수 있으면 null.
   * 'level' = 열람 레벨 부족, 'member' = 회원 전용 글 (로그인/비밀번호 필요)
   *
   * @param \DocumentItem $oDocument
   * @param object $config
   * @return string|null
   */
  public static function getRestriction($oDocument, $config) {
    if (Session::isAdmin()) {
      return null;
    }
    if (self::isLevelRestricted($oDocument, $config)) {
      return 'level';
    }
    if (self::isMemberOnly($oDocument, $config) && !self::canView($config)) {
      return 'member';
    }
    return null;
  }

  /**
   * 현재 방문자가 회원 전용 글을 볼 수 있는지 확인한다.
   * 관리자는 항상 가능, 허용 그룹을 지정하지 않으면 로그인 회원 모두 가능. 검색봇 허용 시 검증된 구글봇·네이버봇도 가능.
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
    if ($config->allow_bots === 'Y' && BotVerify::isVerified()) {
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
   * 비밀번호 열람을 쓰는지 확인한다. '제목(썸네일) 가리기'·'제목(썸네일) 출력' 방식에서 '로그인 또는 비밀번호'를 선택했을 때만 해당한다.
   *
   * @param object $config
   * @return bool
   */
  public static function usePassword($config) {
    return $config->use_password === 'Y' && in_array($config->list_mode, ['mask', 'show'], true);
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
    if (Session::isAdmin()) {
      return [];
    }

    $moduleSrls = array_filter(array_map('intval', $moduleSrls));
    if ($moduleSrls && $config->module_srls) {
      $moduleSrls = array_intersect($moduleSrls, $config->module_srls);
      if (!$moduleSrls) {
        return [];
      }
    }
    $targets = implode(',', $moduleSrls ?: $config->module_srls) ?: null;

    $hidden = [];

    // 회원 전용 글
    if (!self::canView($config)) {
      $args = new \stdClass;
      $args->module_srl = $targets;
      $args->eid = $config->eid;
      $args->var_value = 'Y';
      $output = executeQueryArray('member_only.getMemberOnlyDocumentSrls', $args);
      if ($output->toBool()) {
        $hidden = array_map('intval', array_column($output->data ?: [], 'document_srl'));
      }
    }

    // 열람 레벨이 방문자 레벨보다 높은 글
    if (!self::isLevelExempt($config)) {
      $viewerLevel = self::getViewerLevel($config);
      $args = new \stdClass;
      $args->module_srl = $targets;
      $args->eid = $config->level_eid;
      $output = executeQueryArray('member_only.getReadLevelDocumentSrls', $args);
      if ($output->toBool()) {
        foreach ($output->data ?: [] as $row) {
          if ((int)trim((string)$row->value) > $viewerLevel) {
            $hidden[] = (int)$row->document_srl;
          }
        }
      }
    }

    return array_values(array_unique($hidden));
  }
}
