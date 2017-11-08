<?php

namespace xenialdan\gameapi;

use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Team{

	/** @var Player[] */
	private $players = [];
	/** @var Player[] */
	private $initialplayers = [];
	/** @var int $max , $min */
	private $max = 1;
	private $min = 1;
	/**
	 * @var string
	 */
	private $name = "";
	/**
	 * @var string
	 */
	private $color = TextFormat::RESET;
	private $spawnOffset;

	/**
	 * Team constructor.
	 * @param string $color The color set for the team, a constant of TextFormat, example: TextFormat::RED
	 * @param string $name The name of the team
	 * @param Player[] $players
	 */
	public function __construct(string $color = TextFormat::RESET, string $name = "", array $players = []){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$this->color = $color;
		$this->name = $name;
		foreach ($players as $player){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			$this->addPlayer($player);
		}
	}

	/**
	 * @param Player $player
	 */
	public function addPlayer(Player $player){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$this->players[$player->getLowerCaseName()] = $player;
	}

	/**
	 * @param Player $player
	 */
	public function removePlayer(Player $player){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		unset($this->players[$player->getLowerCaseName()]);
	}

	/**
	 * Tests if the players are in this team
	 * @param Player[] ...$players
	 * @return bool
	 */
	public function inTeam(Player ...$players){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		foreach ($players as $player){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
			if (!isset($this->players[$player->getLowerCaseName()])) return false;
		}
		return true;
	}

	public function __toString(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return "Team " . $this->getColor() . $this->getName() . TextFormat::RESET . ", players: " . (implode(", ", array_keys($this->getPlayers())));
	}

	/**
	 * @return string
	 */
	public function getColor(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return $this->color;
	}

	/**
	 * @param string $color
	 */
	public function setColor(string $color){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$this->color = $color;
	}

	/**
	 * @return string
	 */
	public function getName(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$this->name = $name;
	}

	/**
	 * @return Player[]
	 */
	public function getPlayers(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return $this->players;
	}

	/**
	 * @return Player[]
	 */
	public function getInitialPlayers(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return $this->initialplayers;
	}

	public function resetInitialPlayers(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$this->initialplayers = [];
	}

	public function updateInitialPlayers(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$this->initialplayers = $this->getPlayers();
	}

	/**
	 * @param int $max
	 */
	public function setMaxPlayers(int $max){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$this->max = $max;
	}

	/**
	 * @return int
	 */
	public function getMaxPlayers(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return $this->max;
	}

	/**
	 * @param int $min
	 */
	public function setMinPlayers(int $min){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$this->min = $min;
	}

	/**
	 * @return int
	 */
	public function getMinPlayers(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return $this->min;
	}

	public function setSpawnOffset(Vector3 $offset){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		$this->spawnOffset = $offset;
	}

	/**
	 * @return Vector3
	 */
	public function getSpawnOffset(){
print __CLASS__ . '-' . __LINE__ . ':';//TODO REMOVE
		return $this->spawnOffset??new Vector3();
	}
}