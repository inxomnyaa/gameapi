<?php

namespace xenialdan\gameapi\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\plugin\Plugin;
use xenialdan\gameapi\API;

class StopGameEvent extends PluginEvent{
	public static $handlerList = null;

	public function __construct(Plugin $game){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		parent::__construct($game);
	}

	public function getGame(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return API::getGame($this->getPlugin()->getName());
	}

	public function getName(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$this->getGame()->getName();
	}
}