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

class WinEvent extends PluginEvent{
	public static $handlerList = null;
	/** @var Arena */
	private $arena;
	private $winner;

	/**
	 * TeamWinEvent constructor.
	 * @param Plugin $plugin
	 * @param Arena $arena
	 * @param Team|Player $winner
	 */
	public function __construct(Plugin $plugin, Arena $arena, $winner){
		parent::__construct($plugin);
		$this->arena = $arena;
		$this->winner = $winner;
	}

	public function announce(){
		$prefix = $this->winner instanceof Player ? "Player " : "Team ";
		Server::getInstance()->broadcastTitle(TextFormat::GREEN . $prefix . $this->winner->getName(), TextFormat::GREEN . ' has won the game ' . $this->arena->getOwningGame()->getName() . '!', -1, -1, -1, Server::getInstance()->getDefaultLevel()->getPlayers());
	}

	public function getGame(){
		return API::getGame($this->getPlugin()->getName());
	}

	/**
	 * @return Player[]
	 */
	public function getWinningPlayers(){
		if($this->winner instanceof Player) return [$this->winner];
		else return $this->winner->getInitialPlayers();
	}
}