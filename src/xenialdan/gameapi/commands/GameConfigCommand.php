<?php

declare(strict_types=1);

namespace xenialdan\gameapi\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xenialdan\gameapi\API;

class GameConfigCommand extends Command
{
    public function __construct()
    {
        parent::__construct("gameconfig", "Modify the game server settings like lobby protection", "/gameconfig");
        $this->setPermission(Permission::DEFAULT_OP);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        /** @var Player $sender */
        $return = $sender->hasPermission($this->getPermission());
        if (!$return) {
            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
            return true;
        }
        $settings = API::getSettings();
        if ($sender instanceof Player) {
            $form = $settings->getUI();
            $sender->sendForm($form);
        } else {
            $sender->sendMessage(print_r($settings->getAll(), true));
        }

        return $return;
    }
}
