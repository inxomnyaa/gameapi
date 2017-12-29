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
	private $name = "unnamed";
	/**
	 * @var string
	 */
	private $color = TextFormat::RESET;
	private $spawnOffset;
	private $spawn;

	/**
	 * Team constructor.
	 * @param string $color The color set for the team, a constant of TextFormat, example: TextFormat::RED
	 * @param string $name The name of the team
	 * @param Player[] $players
	 */
	public function __construct(string $color = TextFormat::RESET, string $name = "", array $players = []){
		$this->setColor($color);
		$this->setName($name);
		foreach ($players as $player){
			$this->addPlayer($player);
		}
	}

	/**
	 * @param Player $player
	 */
	public function addPlayer(Player $player){
		$this->players[$player->getLowerCaseName()] = $player;
	}

	/**
	 * @param Player $player
	 */
	public function removePlayer(Player $player){
		unset($this->players[$player->getLowerCaseName()]);
	}

	/**
	 * Tests if the players are in this team
	 * @param Player[] ...$players
	 * @return bool
	 */
	public function inTeam(Player ...$players){
		foreach ($players as $player){
			if (!isset($this->players[$player->getLowerCaseName()])) return false;
		}
		return true;
	}

	public function __toString(){
		return "Team " . $this->getColor() . $this->getName() . TextFormat::RESET . ", players: " . (implode(", ", array_keys($this->getPlayers())));
	}

	/**
	 * @return string
	 */
	public function getColor(){
		return $this->color;
	}

	/**
	 * @param string $color
	 */
	public function setColor(string $color){
		$this->color = $color;
	}

	/**
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name){
		$this->name = $name;
	}

	/**
	 * @return Player[]
	 */
	public function getPlayers(){
		return $this->players;
	}

	/**
	 * @return Player[]
	 */
	public function getInitialPlayers(){
		return $this->initialplayers;
	}

	public function resetInitialPlayers(){
		$this->initialplayers = [];
	}

	public function updateInitialPlayers(){
		$this->initialplayers = $this->getPlayers();
	}

	/**
	 * @param int $max
	 */
	public function setMaxPlayers(int $max){
		$this->max = $max;
	}

	/**
	 * @return int
	 */
	public function getMaxPlayers(){
		return $this->max;
	}

	/**
	 * @param int $min
	 */
	public function setMinPlayers(int $min){
		$this->min = $min;
	}

	/**
	 * @return int
	 */
	public function getMinPlayers(){
		return $this->min;
	}

	/**
	 * @param Vector3 $offset
	 */
	public function setSpawnOffset(Vector3 $offset){
		$this->spawnOffset = $offset;
	}

	/**
	 * @return Vector3
	 */
	public function getSpawnOffset(){
		return $this->spawnOffset??new Vector3();
	}

	/**
	 * @param Vector3 $vector3
	 */
	public function setSpawn(Vector3 $vector3){
		$this->spawn = $vector3;
	}

	/**
	 * @return Vector3
	 */
	public function getSpawn(){
		return $this->spawn??new Vector3();
	}
}