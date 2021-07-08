<?php

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Exception;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;


/**
 * Generic command
 *
 * Gets executed for generic commands, when no other appropriate one is found.
 */
class ZiCommand extends UserCommand
{
  /**
   * @var string
   */
  protected $name = 'zi';

  /**
   * @var string
   */
  protected $description = '查字的讀音';



  protected $usage = 'hello';
  /**
   * @var string
   */
  protected $version = '0.0.0a';

  /**
   * Main command execution
   *
   * @return ServerResponse
   * @throws TelegramException
   */
  public function execute(): ServerResponse
  {
    $message = $this->getMessage();
    $query = $message->getText(true);
    try {
      $text = $this->query($query);
      if (empty($text)) $text = "冇搵到 No Result Found";
      return $this->replyToChat($text, [
        'reply_to_message_id' => $message->getMessageId(),
        // 'parse_mode' => 'HTML',
      ]);
    } catch (\Throwable $th) {
      return $this->replyToChat("搵唔到 <b>{$query}</b> 嘅资料, 因為 " . PHP_EOL . "No results for <b>{$query}</b> Because" . PHP_EOL . "<i>{$th->getMessage()}</i> ", [
        'reply_to_message_id' => $message->getMessageId(),
        // 'parse_mode' => 'HTML',
      ]);
    }
  }

  protected function query(string $query): string
  {
    try {
      $client = new \GuzzleHttp\Client();
      $res = $client->request('GET', "https://jyutdict.org/api/v0.9/detail?" . http_build_query([
        'chara' => trim($query),
      ]));
      if ($res->getStatusCode() == 200) {
        try {

          $decoded = json_decode($res->getBody(), true);
          if (!empty($decoded)) {
            $decoded = current($decoded);
            $ret = "";
            $datas = [];
            array_push($datas, "字" . PHP_EOL . json_encode($decoded['字'], JSON_UNESCAPED_UNICODE));
            array_push($datas, "韻 書" . PHP_EOL . json_encode($decoded['韻書'], JSON_UNESCAPED_UNICODE));
            array_push($datas, "地 方" . PHP_EOL . json_encode($decoded['各地'], JSON_UNESCAPED_UNICODE));
            $ret = join(PHP_EOL . "------" . PHP_EOL, $datas);
            return $ret;
          }
        } catch (\throwable $th) {
          throw new Exception("Parsing Error: " . $th->getMessage());
        }
      } else {
        return "API 請求失敗 Request Failed";
      }
    } catch (\Throwable $th) {
      throw $th;
    }
    return "";
  }
}
