<?php

namespace xenialdan\gameapi\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\plugin\Plugin;
use xenialdan\gameapi\Game;

class RegisterGameEvent extends PluginEvent{
	public static $handlerList = null;

    public function __construct(Game $plugin)
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

    public function call(): void
    {
        $this->getGame()->getServer()->getLogger()->notice('Registered game ' . $this->getName() . ' by ' . $this->getGame()->getAuthors());
        parent::call();
    }
}