<?php

namespace Rhymix\Modules\Member_Only\Controllers;

use Rhymix\Framework\Cache;
use Rhymix\Framework\Session;
use Rhymix\Modules\Member_Only\Models\Config;
use Rhymix\Modules\Member_Only\Models\Filter;

/**
 * MemberOnly unlock controller. 로그인 대신 비밀번호로 회원 전용 글을 열람한다.
 */
class Unlock extends Base {

  /**
   * 비밀번호 입력 화면. 로그인 링크도 함께 보여준다.
   */
  public function dispMember_onlyPassword() {
    $config = Config::getConfig();
    if (!Filter::usePassword($config)) {
      return $this->setError(-1, '비밀번호 열람이 사용 설정되어 있지 않습니다.');
    }

    $mid = preg_replace('/[^a-zA-Z0-9_]/', '', (string)\Context::get('mo_mid'));
    $srl = (int)\Context::get('mo_srl');

    \Context::set('mo_mid', $mid);
    \Context::set('mo_srl', $srl);
    \Context::set('pw_url', getNotEncodedUrl('', 'module', 'member_only', 'act', 'dispMember_onlyPassword', 'mo_mid', $mid, 'mo_srl', $srl));
    \Context::set('login_url', $mid && $srl
      ? getNotEncodedUrl('mid', $mid, 'act', 'dispMemberLoginForm', 'referer_url', getNotEncodedFullUrl('', 'mid', $mid, 'document_srl', $srl))
      : getNotEncodedUrl('', 'act', 'dispMemberLoginForm'));
    \Context::set('is_member', Session::isMember());

    $this->setTemplatePath($this->module_path . 'views');
    $this->setTemplateFile('password.blade.php');
  }

  /**
   * 비밀번호 확인. 맞으면 이 브라우저 세션에서 회원 전용 글을 열람할 수 있게 하고, 원래 글로 돌려보낸다.
   * 무차별 대입을 막기 위해 IP별로 실패 횟수를 제한한다.
   */
  public function procMember_onlyUnlock() {
    $config = Config::getConfig();
    if (!Filter::usePassword($config)) {
      return $this->setError(-1, '비밀번호 열람이 사용 설정되어 있지 않습니다.');
    }

    $key = 'member_only:unlock_fail:' . md5(\RX_CLIENT_IP);
    $fails = (int)Cache::get($key);
    if ($fails >= 10) {
      return $this->setError(-1, '비밀번호를 여러 번 잘못 입력했습니다. 잠시 후 다시 시도해 주세요.');
    }

    if (!Filter::checkPassword($config, \Context::get('password'))) {
      Cache::set($key, $fails + 1, 600);
      return $this->setError(-1, '비밀번호가 올바르지 않습니다.');
    }

    Cache::delete($key);
    Session::set('member_only_unlock', Filter::getUnlockToken($config));

    // 되돌아갈 주소는 입력값을 그대로 쓰지 않고 mid·글 번호로 새로 만든다.
    $mid = preg_replace('/[^a-zA-Z0-9_]/', '', (string)\Context::get('mo_mid'));
    $srl = (int)\Context::get('mo_srl');
    $this->setRedirectUrl($mid && $srl ? getNotEncodedUrl('', 'mid', $mid, 'document_srl', $srl) : getNotEncodedUrl(''));
  }
}
