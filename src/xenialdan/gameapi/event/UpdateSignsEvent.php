<?php

namespace xenialdan\gameapi\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\level\Level;
use pocketmine\plugin\Plugin;
use pocketmine\tile\Sign as SignTile;
use pocketmine\utils\TextFormat;
use xenialdan\gameapi\Arena;

class UpdateSignsEvent extends PluginEvent{
	public static $handlerList = null;
	private $levels;
	/** @var Arena */
	private $arena;
	private $status;

	/**
	 * UpdateSignsEvent constructor.
	 * @param Plugin $plugin
	 * @param Level[] $levels
	 * @param Arena $arena
	 * @param int $status
	 */
	public function __construct(Plugin $plugin, $levels, Arena $arena, int $status = Arena::IDLE){
		parent::__construct($plugin);
		$this->levels = $levels;
		$this->arena = $arena;
		$this->status = $status;
	}

	public function updateSigns(){
		foreach ($this->levels as $level){
			if (!$level instanceof Level) continue;
			foreach ($level->getTiles() as $tile){
				if (!$tile instanceof SignTile){
					continue;
				} else{
					$lines = $tile->getText();
					if (strtolower(TextFormat::clean($lines[0])) === strtolower("[" . $this->getPlugin()->getName() . "]")){
						if (TextFormat::clean($lines[1]) === $this->arena->getLevelName()){
							$status = $this->status;
							switch ($status){
								case Arena::IDLE: {
									$status = "Empty/Idle";
									break;
								}
								case Arena::WAITING: {
									$status = "Need players";
									break;
								}
								case Arena::STARTING: {
									$status = "Starting..";
									break;
								}
								case Arena::INGAME: {
									$status = "Running";
									break;
								}
								case Arena::STOP: {
									$status = "Reloading..";
									break;
								}
							}
							$playerline = TextFormat::AQUA . "[" . (count($this->arena->getPlayers()) === $this->arena->getMaxPlayers() ? TextFormat::RED : TextFormat::GREEN) . count($this->arena->getPlayers()) . TextFormat::AQUA . "|" . (count($this->arena->getPlayers()) === $this->arena->getMaxPlayers() ? TextFormat::RED : TextFormat::GREEN) . $this->arena->getMaxPlayers() . '-' . $this->arena->getMaxPlayers() . TextFormat::AQUA . "]";
							$tile->setText($lines[0], $lines[1], $status, $playerline);
						}
					}
				}
			}
		}
	}
}