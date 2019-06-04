<?php

declare(strict_types=1);

namespace xenialdan\gameapi\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use xenialdan\gameapi\API;
use xenialdan\gameapi\Game;

class GamesCommand extends Command
{
    public function __construct()
    {
        parent::__construct("games", "List games", "/games");
        $this->setPermission(Permission::DEFAULT_TRUE);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        /** @var Player $sender */
        $return = $sender->hasPermission($this->getPermission());
        if (!$return) {
            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
            return true;
        }
        $sender->sendMessage(TextFormat::GOLD . "=== Available games ===");
        /** @var Game $game */
        foreach (API::getGames() as $game)
            $sender->sendMessage(($game->isEnabled() ? TextFormat::GREEN : TextFormat::RED) . $game->getPrefix() . ($game->isEnabled() ? TextFormat::GREEN : TextFormat::RED) . " v" . $game->getDescription()->getVersion() . " by " . TextFormat::AQUA . $game->getAuthors() . ($game->isEnabled() ? TextFormat::GREEN : TextFormat::RED) . ($game->getDescription()->getDescription() !== "" ?: ($game->isEnabled() ? TextFormat::GREEN : TextFormat::RED) . $game->getDescription()->getDescription()));
        return $return;
    }
}
