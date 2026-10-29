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
      }
    }
  }
}
