<?php

declare(strict_types=1);

return [
  'bot_token' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
  'chat_id'   => getenv('TELEGRAM_CHAT_ID') ?: '',
  'username'  => getenv('TELEGRAM_USERNAME') ?: '',
];
