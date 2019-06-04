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

class GameStatusCommand extends Command
{
    public function __construct()
    {
        parent::__construct("gamestatus", "See the status of all games (performance, state, players) or a specific game", "/gamestatus [game]");
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
        $tocheck = [];
        if (!empty($args)) {
            if (($game = API::getGame(($name = array_shift($args)))) instanceof Game) {
                $tocheck[] = $game;
            } else {
                $sender->sendMessage(TextFormat::RED . "Game $name cannot be found. Check /games");
                return true;
            }
        } else {
            $tocheck = API::getGames();
        }
        $sender->sendMessage(TextFormat::GOLD . "=== Game Status ===");
        /** @var Game $game */
        foreach ($tocheck as $game) {
            $enabledColor = $game->isEnabled() ? TextFormat::GREEN : TextFormat::RED;
            $sender->sendMessage(TextFormat::AQUA . "=== " . $enabledColor . $game->getName() . TextFormat::AQUA . " ===");
            if ($game->isEnabled()) {
                foreach ($game->getStatusLines() as $statusKey => $statusLine) {
                    $sender->sendMessage($statusKey . ": " . $statusLine);
                }
            }
        }
        return $return;
    }
}
