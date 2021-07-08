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
class FaanCommand extends UserCommand
{
  /**
   * @var string
   */
  protected $name = 'faan';

  /**
   * @var string
   */
  protected $description = '在泛粵字表中查字音 Querying pronunciation/character in Pan-Cantonese Chart';



  protected $usage =
    '/faan 字/音!地方限定 參數 (參數之間毋需空隔)' . PHP_EOL . PHP_EOL .

    '以綜合/地方音 或 錔（口語）字（不含一般書面語用字）查意思 查音 查字' . PHP_EOL . PHP_EOL .
    
    '查詢字串後貼!可在查音時指定地方音 目前默認為廣州音「穗」' . PHP_EOL . PHP_EOL .
    
    '參數「b」可以關鍵字查詢釋義' . PHP_EOL . PHP_EOL .

    '參數「r」可在查詢句中使用正則表達式' . PHP_EOL . PHP_EOL .

    '案例： /faan ma\[tk][1-6]!港 r   在香港粵語中 查尋所有mat mak的音 並 列出釋義 及 其它地方的讀音 r為在查詢串啟用正則表達式' . PHP_EOL .
    
    '案例： /faan 甩 b   在釋義中 查 甩 這個字的用法 並 列出 所有 地方音的讀音' . PHP_EOL . PHP_EOL .

    '/faan Character/Pronunciation Arguments (No need to split between Arguments)' . PHP_EOL . PHP_EOL .

    'Add <!> after query string could specified area when you trying to query via pronunciation,default set to Canton(GwongZau) Spell now.'. PHP_EOL . PHP_EOL .

    'Query for Colloquial Cantonese Character/Composited or Regional Cantonese Pronunciation (General Chinese Not included)' . PHP_EOL . PHP_EOL .

    'Argument <!> would list Non-Cantonese Pronunciation' . PHP_EOL . PHP_EOL .

    'Argument <b> Could look for Meanning with keyword' . PHP_EOL . PHP_EOL .

    'Argument <r> would use regular expression on query statement' . PHP_EOL . PHP_EOL .

    'Example: /faan ma\[tk]\[1-6]!江 r  search Pronunciation of "mat" "mak" in GongMun and list all meanning and prounciation of others area Argument<r> represents using regular expression on query string  ' . PHP_EOL .
    'Example: /faan 甩 b search 甩 in meanning and list out pronucation';
  /**
   * @var string
   */
  protected $version = '0.1.0a';

  /**
   * Main command execution
   *
   * @return ServerResponse
   * @throws TelegramException
   */
  public function execute(): ServerResponse
  {
    $message = $this->getMessage();
    $raw_text = $message->gettext(true);
    $query = current(explode(" ", $raw_text));
    $args = str_split(array_pop(explode(" ", trim($raw_text))));
    try {
      $text = $this->processing($query, $args);
      if (empty($text)) $text = "冇搵到 No Result Found";
      return $this->replyToChat($text, [
        'reply_to_message_id' => $message->getMessageId(),
        // 'parse_mode' => 'HTML',
      ]);
    } catch (Exception $e) {
      return $this->replyToChat("搵唔到 <b>{$query}</b> 嘅资料, 因為 " . PHP_EOL . "No results for <b>{$query}</b> Because" . PHP_EOL . "<i>{$e->getMessage()}</i> ", [
        'reply_to_message_id' => $message->getMessageId(),
        'parse_mode' => 'HTML',
      ]);
    }
  }

  protected function processing(string $raw_query,array $args): string
  {
    try {
      $query = "";
      $colLimit = "";
      try {
        $split = explode("!",$raw_query);
        $query = current($split);
        $colLimit = count($split) > 1 ? end($split) : "";
      } catch (\Throwable $th) {
        throw $th;
      }

      $client = new \GuzzleHttp\Client();
      $requestArgs = [
        'query' => $query
      ];
      if (empty($query)) return "請輸入問詢的字/音 or /help faan for help! please enter query for chracter/pronunciation";
      if ($query === "help") return $this->usage;
      in_array('r',$args) ? $requestArgs['regex'] = "" : null;
      in_array('b',$args) ? $requestArgs['b'] = "" : null;
      array_push($args,"a");
      if(!empty(preg_match('/^[a-zA-Z]/', $query))) {
        $requestArgs['col'] = "穗";
      }
      !empty($colLimit) ? $requestArgs['col'] = $colLimit : null;
      $res = $client->request('GET', "https://jyutdict.org/api/v0.9/sheet?" . http_build_query($requestArgs));
      if ($res->getStatusCode() == 200) {
        $decoded = json_decode($res->getBody(), true);
        if (!empty($decoded)) {
       unset($decoded[0]);
          try {
            $ret = "";
            foreach ($decoded as $row) {
              $ret .= $this->parse_single_result_json($row, $args);
            }
            return $ret;
          } catch (\Throwable $th) {
            throw $th;
          }
        }
      } else {
        throw new Exception("API REQUEST FAILED");
      }
    } catch (\Throwable $th) {
      throw new Exception("Request Process Error " + $th->getMessage());
    }// end of first try 
    return "";
  }

  //translate from python
  private function parse_single_result_json($entry, $arg): string
  {
    try {
      $split = PHP_EOL . "===========" . PHP_EOL;
      $chara =  ($entry['繁'] === "？" or $entry['繁'] === "?") ? "□" : str_replace("見", "", str_replace('歸', '', $entry['繁']));
      $pron = str_replace("!", "", $entry['綜']);
      $adapted = !empty($entry['俗/常']) ? "({$entry['俗/常']})" : "";
      $ids =  !empty($entry['IDS']) ? "[{$entry['IDS']}]" : "";

      //processing grammarly marking
      $marking = !empty($entry['語法']) ? array_map(function ($v) {
        return sprintf("[{$v}]");
      }, array_filter(explode(';', $entry['語法']), function ($ele) {
        return $ele !== "";
      })) : [];

      $meaning = (count($marking) === substr_count($entry['釋義'], '；') + 1)
        ? join("；", array_filter(array_map(function ($mark, $mean) {
          if (empty($mark) || empty($mean)) return null;
          return sprintf("%s %s", $mark, trim($mean));
        }, $marking, explode(";", $entry['釋義'])), function ($v) {
          return !empty($v);
        }))
        : $entry['釋義'];

      # output header
      $string_built = "【{$chara}{$ids}】{$adapted} 綜合音: {$pron}" . PHP_EOL .
        "{$meaning}" . PHP_EOL;

      if (in_array("a", $arg)) {
        $regional = !in_array("!", $arg);  ## regional == 僅輸出域內音
        $recording = false;  # 當 recording == True 時，對應欄位纔會被打印出來

        $count_jam = 0;
        foreach ($entry as $k => $v) {
          if ($k === "IDS") { // 開始位 依表序
            $recording = true;
          } else if ($k === "釋義") {
            if ($regional) {
              break;
            } else {
              $recording = false;
            }
          } else if ($k === "上古") {
            $string_built .= PHP_EOL;
            $recording = true;
          } else if (!empty($v) && $recording) {
            $string_built .= sprintf("{$k}:{$v}    \t");
            $count_jam += 1;
            $count_jam % 4 === 0 ? $string_built .= PHP_EOL : null; #Wrap after 4 Pronunciations outputed
          }
        }
      }
      return $string_built . $split;
    } catch (\Throwable $th) {
      throw new Exception("Parsing Error");
    }
  }
}
