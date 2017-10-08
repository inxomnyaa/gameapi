<?php

namespace xenialdan\gameapi\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\plugin\Plugin;

class StopGameEvent extends PluginEvent{
	public static $handlerList = null;

	public function __construct(Plugin $game){
		parent::__construct($game);
	}
}