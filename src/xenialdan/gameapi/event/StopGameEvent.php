<?php

namespace xenialdan\gameapi\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\plugin\Plugin;
use xenialdan\gameapi\API;

class StopGameEvent extends PluginEvent{
	public static $handlerList = null;

	public function __construct(Plugin $game){
		parent::__construct($game);
	}

	public function getGame(){
		return API::getGame($this->getPlugin()->getName());
	}

	public function getName(){
		$this->getGame()->getName();
	}
}