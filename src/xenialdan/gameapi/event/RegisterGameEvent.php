<?php

namespace xenialdan\gameapi\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\plugin\Plugin;
use xenialdan\gameapi\Game;

class RegisterGameEvent extends PluginEvent{
	public static $handlerList = null;

    public function __construct(Plugin $plugin)
    {
		parent::__construct($plugin);
	}

	/**
	 * @return Game|Plugin
	 */
	public function getGame(){
        return $this->getPlugin();
	}

	public function getName(){
		return $this->getGame()->getName();
	}
}