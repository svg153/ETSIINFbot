<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\commands\system;

use app\commands\base\BaseSystemCommand;
use Longman\TelegramBot\Request;

/**
 * Generic command
 */
class GenericCommand extends BaseSystemCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'Generic';
    protected $description = 'Handles generic commands or is executed by default when a command is not found';
    protected $version = '1.0.1';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();

        //You can use $command as param
        $chat_id = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $command = $message->getCommand();

        if (in_array($user_id, $this->telegram->getAdminList()) && strtolower(substr($command, 0, 5)) == 'whois') {
            return $this->telegram->executeCommand('whois', $this->update);
        }

        $data = [
            'chat_id' => $chat_id,
            'text'    => 'Lo siento, pero el comando /' . $command . ' no ha sido encontrado :(',
        ];

        return Request::sendMessage($data);
    }
}
