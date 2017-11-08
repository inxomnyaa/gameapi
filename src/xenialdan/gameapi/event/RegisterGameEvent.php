<?php

namespace xenialdan\gameapi\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\plugin\Plugin;
use xenialdan\gameapi\Game;

class RegisterGameEvent extends PluginEvent{
	public static $handlerList = null;

	/** @var Plugin */
	private $game;

	public function __construct(Plugin $plugin, Plugin $game){
		parent::__construct($plugin);
		$this->game = $game;
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin(): Plugin{
		return $this->game;
	}

	/**
	 * @return Game|Plugin
	 */
	public function getGame(){
		return $this->game;
	}

	public function getName(){
		$this->getGame()->getName();
	}
}