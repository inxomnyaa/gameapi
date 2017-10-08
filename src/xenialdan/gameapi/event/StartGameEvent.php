<?php

namespace xenialdan\gameapi\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\plugin\Plugin;

class StartGameEvent extends PluginEvent{
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
}