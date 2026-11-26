<?php

namespace Rhymix\Modules\Member_Only\Models;

use Rhymix\Framework\Cache;

/**
 * MemberOnly bot verification model.
 * User-Agent는 누구나 위조할 수 있으므로, 역방향 DNS 조회 후 다시 정방향 조회해서 IP가 일치할 때만 검색봇으로 인정한다.
 */
class BotVerify {

  /**
   * 허용할 검색봇. ua: User-Agent에 포함된 이름, host: 역방향 DNS 결과로 허용하는 호스트명 패턴.
   * 구글: googlebot.com / google.com, 네이버(Yeti): naver.com (각 사 공식 안내 기준)
   */
  const BOTS = [
    'google' => ['ua' => 'Googlebot', 'host' => '/\.(googlebot|google)\.com$/i'],
    'naver'  => ['ua' => 'Yeti', 'host' => '/\.naver\.com$/i'],
  ];

  /** @var bool|null 같은 요청 안에서는 한 번만 판정한다. */
  private static $result = null;

  /**
   * 현재 방문자가 검증된 검색봇인지 확인한다.
   *
   * @return bool
   */
  public static function isVerified() {
    if (self::$result !== null) {
      return self::$result;
    }
    return self::$result = self::verify((string)\RX_CLIENT_IP, (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
  }

  /**
   * UA가 봇을 주장할 때만 DNS를 조회하고, 결과는 IP별로 캐시한다. (성공 24시간, 실패 1시간)
   *
   * @param string $ip
   * @param string $ua
   * @return bool
   */
  private static function verify($ip, $ua) {
    if ($ip === '' || $ua === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
      return false;
    }

    $patterns = [];
    foreach (self::BOTS as $bot) {
      if (stripos($ua, $bot['ua']) !== false) {
        $patterns[] = $bot['host'];
      }
    }
    if (!$patterns) {
      return false;
    }

    $key = 'member_only:bot:' . md5($ip);
    $cached = Cache::get($key);
    if ($cached !== null) {
      return (int)$cached === 1;
    }

    $ok = false;
    $host = @gethostbyaddr($ip);
    if ($host && $host !== $ip) {
      foreach ($patterns as $pattern) {
        if (preg_match($pattern, $host) && self::resolvesTo($host, $ip)) {
          $ok = true;
          break;
        }
      }
    }

    Cache::set($key, $ok ? 1 : 0, $ok ? 86400 : 3600);
    return $ok;
  }

  /**
   * 호스트명을 정방향 조회했을 때 원래 IP가 나오는지 확인한다. (IPv4/IPv6)
   *
   * @param string $host
   * @param string $ip
   * @return bool
   */
  private static function resolvesTo($host, $ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
        if (isset($record['ipv6']) && @inet_pton($record['ipv6']) === @inet_pton($ip)) {
          return true;
        }
      }
      return false;
    }
    return in_array($ip, @gethostbynamel($host) ?: [], true);
  }
}
