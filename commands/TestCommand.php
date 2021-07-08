<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class TestCommand extends UserCommand
{
  protected $name = 'test';
  protected $description = 'Testing Command 打我冇用';
  protected $usage = '/test 你打我就say hello';
  protected $version = '0.0';

  public function execute(): ServerResponse
  {
    $message = $this->getMessage();

    $chat_id = $message->getChat()->getId();

    $data = [
      'chat_id' => $chat_id,
      'text' => 'Hello 我同你say緊hi啊',
    ];

    return Request::sendMessage($data);
  }
}
