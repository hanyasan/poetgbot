<?php

namespace App\TelegramBotCommands;

use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class HelpCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'help';

    /**
     * @var string
     */
    protected $description = 'Show bot commands help';

    /**
     * @var string
     */
    protected $usage = '/help or /help <command>';

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $commandStr = trim($message->getText(true));

        $userCommands = $this->getUserCommands();

        // If no command parameter is passed, show the list.
        if ($commandStr === '') {
            $text = '*Commands List*:' . PHP_EOL;

            foreach ($userCommands as $userCommand) {
                $text .= '/' . $userCommand->getName() . ' - ' . $userCommand->getDescription() . PHP_EOL;
            }

            $text .= PHP_EOL . 'For exact command help type: /help <command>';

            return $this->replyToChat($text, ['parse_mode' => 'markdown']);
        }

        $commandStr = str_replace('/', '', $commandStr);

        if (isset($userCommands[$commandStr])) {
            $command = $userCommands[$commandStr];

            return $this->replyToChat(sprintf(
                'Command: %s (v%s)' . PHP_EOL .
                'Description: %s' . PHP_EOL .
                'Usage: %s',
                $command->getName(),
                $command->getVersion(),
                $command->getDescription(),
                $command->getUsage()
            ), ['parse_mode' => 'markdown']);
        }

        return $this->replyToChat('No help available: Command `/' . $commandStr . '` not found', ['parse_mode' => 'markdown']);
    }

    /**
     * Get all available User and Admin commands to display in the help list.
     *
     * @return Command[]
     * @throws TelegramException
     */
    protected function getUserCommands(): array
    {
        /** @var Command[] $allCommands */
        $allCommands = $this->telegram->getCommandsList();

        // Only get enabled Admin and User commands that are allowed to be shown.
        $commands = array_filter($allCommands, function ($command): bool {
            return ! $command->isSystemCommand() && $command->showInHelp() && $command->isEnabled();
        });

        // Filter out all User commands
        $userCommands = array_filter($commands, function ($command): bool {
            return $command->isUserCommand();
        });

        ksort($userCommands);

        return $userCommands;
    }
}
