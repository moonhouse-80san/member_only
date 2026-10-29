<?php

namespace Rhymix\Modules\Member_Only\Controllers;

/**
 * MemberOnly install/update handler.
 */
class Install extends Base {

  /**
   * Module install callback.
   *
   * @return \BaseObject
   */
  public function moduleInstall() {
    return new \BaseObject();
  }

  /**
   * Check if the module needs to be updated.
   *
   * @return bool
   */
  public function checkUpdate() {
    return false;
  }

  /**
   * Module update callback.
   *
   * @return \BaseObject
   */
  public function moduleUpdate() {
    return new \BaseObject();
  }

  /**
   * Cache recompile callback.
   *
   * @return void
   */
  public function recompileCache() {

  }
}
