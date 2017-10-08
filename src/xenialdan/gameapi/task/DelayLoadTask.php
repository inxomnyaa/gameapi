<?php

namespace xenialdan\gameapi\task;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use xenialdan\gameapi\Arena;

class DelayLoadTask extends PluginTask{

	private $arena;
	private $levelname;

	/**
	 * DelayLoadTask constructor.
	 * @param Plugin $plugin
	 * @param Arena $arena
	 * @param string $levelname
	 */
	public function __construct(Plugin $plugin, Arena $arena, string $levelname){
		parent::__construct($plugin);
		$this->arena = $arena;
		$this->levelname = $levelname;
	}

	public function onRun(int $currentTick){
		if ($this->arena instanceof Arena){
			while (!Server::getInstance()->isLevelLoaded($this->levelname))
				Server::getInstance()->broadcastMessage('Level ' . $this->levelname . (Server::getInstance()->loadLevel($this->levelname) ? ' successfully' : ' NOT') . ' reloaded!');
			$this->arena->setLevel(Server::getInstance()->getLevelByName($this->levelname));
			//Prevents changing the level
			$this->arena->getLevel()->setAutoSave(false);
			return;
		} else
			$this->getOwner()->getServer()->getScheduler()->cancelTask($this->getTaskId());
		return;
	}
}