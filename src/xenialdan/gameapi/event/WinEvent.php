<?php

namespace xenialdan\gameapi\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use xenialdan\gameapi\API;
use xenialdan\gameapi\Arena;
use xenialdan\gameapi\Team;

class WinEvent extends PluginEvent
{
    public static $handlerList = null;
    /** @var Arena */
    private $arena;
    private $winner;

    /**
     * WinEvent constructor.
     * @param Plugin $plugin
     * @param Arena $arena
     * @param Team|Player $winner
     */
    public function __construct(Plugin $plugin, Arena $arena, $winner)
    {
        parent::__construct($plugin);
        $this->arena = $arena;
        $this->winner = $winner;
    }

    public function announce()
    {
        $prefix = $this->winner instanceof Player ? "Player " : "Team ";
        Server::getInstance()->broadcastTitle(TextFormat::GREEN . $prefix . $this->winner->getName(), TextFormat::GREEN . ' has won the game ' . $this->arena->getOwningGame()->getPrefix() . '!', -1, -1, -1, $this->getInitialPlayers());
        Server::getInstance()->broadcastMessage(TextFormat::GREEN . $prefix . $this->winner->getName() . TextFormat::GREEN . ' has won the game ' . $this->arena->getOwningGame()->getPrefix() . '!', $this->getInitialPlayers());
    }

    public function getGame()
    {
        return API::getGame($this->getPlugin()->getName());
    }

    /**
     * @return Player[]
     */
    public function getWinningPlayers()
    {
        if ($this->winner instanceof Player) return [$this->winner];
        else return $this->winner->getInitialPlayers();
    }

    /**
     * @return Player[]
     */
    public function getInitialPlayers()
    {
        $arena = $this->arena;
        $teams = $arena->getTeams();
        $originals = [];
        foreach ($teams as $team) {
            $originals = array_merge($originals, $team->getInitialPlayers());
        }
        return array_filter($originals, function ($player): bool {
            return $player instanceof Player && $player->isOnline();
        });
    }
}