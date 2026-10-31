<?php

namespace Rhymix\Modules\Member_Only\Controllers;

use Rhymix\Framework\Session;
use Rhymix\Modules\Member_Only\Models\Config;
use Rhymix\Modules\Member_Only\Models\Filter;

/**
 * MemberOnly event handlers (triggers).
 *
 * 설정한 게시판에서 확장변수(설정한 식별자)에 Y 가 포함된 게시글을
 * 허용되지 않은 방문자에게 숨깁니다.
 */
class EventHandlers extends Base {

  /**
   * moduleObject.proc (before) - 허용되지 않은 방문자의 회원 전용 글 열람을 차단한다.
   *
   * @param \ModuleObject $obj
   * @return \BaseObject|void
   */
  public function beforeProc($obj) {
    if (!in_array($obj->act, ['dispBoardContent', 'getBoardCommentPage'], true)) {
      return;
    }

    $config = Config::getConfig();
    $documentSrl = (int)\Context::get('document_srl');
    if (!$documentSrl || Filter::canView($config)) {
      return;
    }

    $oDocument = \DocumentModel::getDocument($documentSrl);
    if ($oDocument->isExists() && Filter::isMemberOnly($oDocument, $config)) {
      $usePassword = Filter::usePassword($config);
      if ($usePassword && $obj->act === 'dispBoardContent') {
        // 로그인 또는 비밀번호: 비밀번호 입력 화면으로 보낸다. (그 화면에서 로그인도 선택할 수 있다)
        $obj->setRedirectUrl(getNotEncodedUrl('', 'module', 'member_only', 'act', 'dispMember_onlyPassword', 'mo_mid', $obj->mid, 'mo_srl', $documentSrl));
      } elseif (!$usePassword && !Session::isMember() && $obj->act === 'dispBoardContent') {
        // 비로그인은 정식 로그인 화면으로 보내고, 로그인 후 이 글로 돌아오게 한다.
        $obj->setRedirectUrl(getNotEncodedUrl('mid', $obj->mid, 'act', 'dispMemberLoginForm', 'referer_url', getNotEncodedFullUrl('', 'mid', $obj->mid, 'document_srl', $documentSrl)));
      }
      if ($usePassword) {
        return new \BaseObject(-1, '회원 전용 글입니다. 로그인하거나 비밀번호를 입력해 주세요.');
      }
      return new \BaseObject(-1, Session::isMember() ? 'msg_not_permitted' : 'msg_not_logged');
    }
  }

  /**
   * document.getDocumentList / getNoticeList (after) - 허용되지 않은 방문자에게 제목과 내용을 가리거나 목록에서 뺀다. (설정: list_mode)
   *
   * @param \BaseObject $output
   * @return void
   */
  public function afterGetDocumentList($output) {
    $config = Config::getConfig();
    if (empty($output->data) || Filter::canView($config)) {
      return;
    }

    foreach ($output->data as $key => $oDocument) {
      if (!($oDocument instanceof \DocumentItem) || !Filter::isMemberOnly($oDocument, $config)) {
        continue;
      }
      if ($config->list_mode === 'hide') {
        unset($output->data[$key]);
        $output->total_count = max(0, (int)$output->total_count - 1);
      } else {
        $oDocument->add('title', '회원 전용 글입니다.');
        $oDocument->add('content', '');
        // 비공개 상태로 바꿔 isAccessible()이 거짓이 되게 한다. (코어 getThumbnail()이 즉시 종료되어 썸네일이 나오지 않음, 메모리상 값이며 DB는 변경되지 않음)
        $oDocument->add('status', 'OTHER');
      }
    }
  }
}
