<?php

namespace xenialdan\gameapi\task;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;
use xenialdan\gameapi\Arena;

class StartTickerTask extends PluginTask{

	private $arena;

	public function __construct(Plugin $plugin, Arena $arena){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		parent::__construct($plugin);
		$this->arena = $arena;
	}

	public function onRun(int $currentTick){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		if ($this->arena instanceof Arena){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			$this->arena->sendTimer($this->getOwner());
			return;
		} else
			$this->getOwner()->getServer()->getScheduler()->cancelTask($this->getTaskId());
		return;
	}
}