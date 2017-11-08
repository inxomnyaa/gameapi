<?php

namespace xenialdan\gameapi;

use pocketmine\level\generator\Generator;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use xenialdan\BossBarAPI\API as BossBarAPI;
use xenialdan\gameapi\event\UpdateSignsEvent;
use xenialdan\gameapi\task\StartTickerTask;

class Arena{
	const IDLE = 0;
	const WAITING = 1;
	const STARTING = 2;
	const INGAME = 3;
	const STOP = 4;
	/** @var Game|Plugin */
	private $owningGame;
	private static $timer;
	/** @var TaskHandler[] */
	private static $tasks;
	public $state = self::IDLE;
	private $levelName;
	private $level = null;
	/** @var Team[] */
	private $teams;
	/** @var int[] */
	public $bossbarids = [];

	/**
	 * Arena constructor.
	 * @param string $levelName
	 * @param Plugin $game
	 */
	public function __construct(string $levelName, Plugin $game){
		$this->owningGame = $game;
		$this->levelName = $levelName;
		try{
			Server::getInstance()->generateLevel($levelName, null, Generator::getGenerator('flat'));
			//reset world
			$path1 = $this->owningGame->getDataFolder() . "worlds\\";
			@mkdir($path1);

			if (!API::copyr($this->owningGame->getServer()->getDataPath() . "worlds\\" . $levelName, $path1 . $levelName)){
				throw new MiniGameException('Could not copy level to plugin..');
			}
			Server::getInstance()->loadLevel($levelName);
			$this->level = Server::getInstance()->getLevelByName($levelName);
			//Prevents changing the level
			#$this->getLevel()->setAutoSave(false);
		} catch (MiniGameException $exception){
			Server::getInstance()->getLogger()->error($exception->getMessage());
		}
	}

	/**
	 * @return Plugin|Game
	 */
	public function getOwningGame(): Game{
		return $this->owningGame;
	}

	/**
	 * Adds a Team
	 * @param Team $team
	 */
	public function addTeam(Team $team){
		$this->teams[] = $team;
	}

	/**
	 * Returns the level for the arena
	 * @return Level
	 */
	public function getLevel(){
		return $this->level;
	}

	/**
	 * @param Level $level
	 */
	public function setLevel(Level $level){
		$this->level = $level;
	}

	/**
	 * Returns the name of the level for the arena
	 * @return string
	 */
	public function getLevelName(){
		return $this->levelName;
	}

	/**
	 * Returns if the player is in this arena
	 * @param $player
	 * @return bool
	 */
	public function inArena($player){
		return !is_null($this->getTeamByPlayer($player));
	}

	/**
	 * Returns the Team a player is in, or none
	 * @param Player $player
	 * @return null|Team
	 */
	public function getTeamByPlayer(Player $player){
		foreach ($this->getTeams() as $team){
			if ($team->inTeam($player)) return $team;
		}
		return null;
	}

	/**
	 * Returns all teams
	 * @return Team[]
	 */
	public function getTeams(){
		return $this->teams;
	}

	/**
	 * Returns the Team by the teamname
	 * @param Player $player
	 * @param string $teamname
	 * @return bool
	 */
	public function joinTeam(Player $player, string $teamname = null){
		if ($this->getState() === self::INGAME || $this->getState() === self::STOP){
			$player->sendMessage(TextFormat::RED . TextFormat::BOLD . "This arena did not stop properly");
			if (count($this->getPlayers()) < 1){
				$player->sendMessage(TextFormat::RED . TextFormat::BOLD . "A mistake happened, trying to stop the arena. Please try again");
				foreach ($this->getPlayers() as $player){
					$this->removePlayer($player);
				}
				API::stop($this->getOwningGame());//TODO stop only arena instead
				API::resetArena($this);
			}
			return false;
		}
		if ($this->getState() === self::INGAME){
			$player->sendMessage(TextFormat::RED . TextFormat::BOLD . "This arena is already running");
			return false;
		}
		if (!is_null($oldteam = $this->getTeamByPlayer($player))){
			$oldteam->removePlayer($player);
		}
		if (empty($teamname)){
			/** @var Team $team */
			$count = [];
			foreach ($this->getTeams() as $team){
				if (count($team->getPlayers()) < $team->getMaxPlayers())
					$count[$team->getName()] = count($team->getPlayers());
			}
			if (!empty($count)) $team = $this->getTeamByName($teamname = array_keys($count, min($count))[0]);
		} elseif (!($team = $this->getTeamByName($teamname)) instanceof Team){
			$player->sendMessage(TextFormat::RED . TextFormat::BOLD . "Sorry, couldn't join team $teamname because it does not exist");
			return false;
		}
		if (is_null($team) || count($team->getPlayers()) >= $team->getMaxPlayers()){
			$player->sendMessage(TextFormat::RED . TextFormat::BOLD . "Sorry, couldn't join because it's full");
			return false;
		}
		var_dump($this->getLevel()->getSafeSpawn()->asVector3());
		$player->teleport($this->getLevel()->getSafeSpawn()->asPosition());
		$team->addPlayer($player);
		Server::getInstance()->getLogger()->notice($player->getName() . ' added to ' . $teamname);
		if ($this->getState() === self::WAITING || $this->getState() === self::IDLE || $this->getState() === self::STARTING){//TODO test
			//TODO bossbar update/send
			if (!isset($this->bossbarids['state'])){
				$this->bossbarids['state'] = BossBarAPI::addBossBar([$player], "Initialising");
			}
			BossBarAPI::sendBossBarToPlayer($player, $this->bossbarids['state'], "Initialising");
			$gamename = $this->owningGame->getName();
			if (count($this->getPlayers()) < $this->getMinPlayers()) Server::getInstance()->broadcastMessage(TextFormat::RED . TextFormat::BOLD . "The game " . $gamename . " needs players!", Server::getInstance()->getDefaultLevel()->getPlayers());
			elseif (count($this->getPlayers()) < $this->getMaxPlayers()) Server::getInstance()->broadcastMessage(TextFormat::DARK_GRAY . TextFormat::BOLD . "The game " . $gamename . " is not full, you can still join it!", Server::getInstance()->getDefaultLevel()->getPlayers());
			BossBarAPI::setTitle($gamename . " Waiting for players... " . count($this->getPlayers()) . '/' . $this->getMinPlayers() . '-' . $this->getMaxPlayers(), $this->bossbarids['state'], $this->getPlayers());

			$this->setState(self::WAITING);
		}
		$player->sendMessage($team->getColor() . TextFormat::BOLD . "You joined the team " . $team->getName());
		$player->setGamemode(Player::ADVENTURE);
		if (($this->getState() === self::WAITING || $this->getState() === self::IDLE || $this->getState() === self::STARTING) && count($this->getPlayers()) >= $this->getMinPlayers()){
			$this->setState(self::STARTING);
			if (isset(self::$tasks['ticker'])) $this->resetTimer();
			$this->startTimer($this->owningGame);
		}
		return true;
	}

	/**
	 * @return Player[]
	 */
	public function getPlayers(){
		$players = [];
		foreach ($this->getTeams() as $team){
			$players = array_merge($players, $team->getPlayers());
		}
		return $players;
	}

	/**
	 * Returns the Team by the teamname
	 * @param string $teamname
	 * @return null|Team
	 * @internal param Player $player
	 */
	public function getTeamByName(string $teamname){
		foreach ($this->getTeams() as $team){
			if ($team->getName() === $teamname) return $team;
		}
		return null;
	}

	/**
	 * @param Plugin|Game $game
	 */
	public function startTimer(Game $game){
		$this->resetTimer();
		$this->setState(self::STARTING);
		self::$tasks['ticker'] = Server::getInstance()->getScheduler()->scheduleRepeatingTask(new StartTickerTask($game, $this), 20);
	}

	/**
	 * @param Plugin|Game $game
	 */
	public function sendTimer(Game $game){
		if (count($this->getPlayers()) < $this->getMinPlayers()){
			Server::getInstance()->broadcastTitle(TextFormat::DARK_RED . "Too less players", "for " . $game->getPrefix() . ', ' . $this->getMinPlayers() . ' players are needed!');
			$this->resetTimer();
			return;
		}
		BossBarAPI::setTitle('Game ' . $game->getPrefix() . ' starts in ' . self::$timer . ' seconds', $this->bossbarids['state'], $this->getPlayers());
		BossBarAPI::setPercentage(self::$timer / 30 * 100, $this->bossbarids['state'], $this->getPlayers());
		self::$timer--;
		if (self::$timer <= 0){
			$this->resetTimer();
			$this->startArena();
		}
	}

	public function resetTimer(){
		$this->setState(self::WAITING);
		if (isset(self::$tasks['ticker'])) Server::getInstance()->getScheduler()->cancelTask(self::$tasks['ticker']->getTaskId());
		unset(self::$tasks['ticker']);
		self::$timer = 30;//TODO config
	}

	public function startArena(){
		foreach ($this->getTeams() as $team){
			$team->resetInitialPlayers();
			$team->updateInitialPlayers();
		}
		$this->setState(self::INGAME);
		$this->getOwningGame()->startArena($this);
	}

	public function stopArena(){ //TODO use this
		foreach ($this->getTeams() as $team){
			$team->resetInitialPlayers();
		}
		$this->setState(self::STOP);
		$this->getOwningGame()->stopArena($this);
	}

	/**
	 * @param int $state
	 */
	public function setState(int $state){
		$this->state = $state;
		Server::getInstance()->getPluginManager()->callEvent(($ev = new UpdateSignsEvent($this->getOwningGame(), [$this->getOwningGame()->getServer()->getDefaultLevel()], $this)));
		$ev->updateSigns();
	}

	/**
	 * @return int
	 */
	public function getState(): int{
		return $this->state;
	}

	/**
	 * @param Player $player
	 */
	public function removePlayer(Player $player){
		$team = $this->getTeamByPlayer($player);
		if (!is_null($team)){
			Server::getInstance()->getLogger()->notice($player->getName() . ' removed');
			$player->setNameTag($player->getDisplayName());
			BossBarAPI::removeBossBar($this->getPlayers(), $this->bossbarids['state']);
			$team->removePlayer($player);
			$player->getInventory()->clearAll();
			$player->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn(Server::getInstance()->getDefaultLevel()->getSpawnLocation()));
			$player->setGamemode(Player::ADVENTURE);//TODO
			$player->setHealth($player->getMaxHealth());
			$player->setFood($player->getMaxFood());
			$player->removeAllEffects();
			$player->setDataFlag(Player::DATA_FLAGS, Player::DATA_FLAG_IMMOBILE, false);
		}
	}

	/**
	 * @return int
	 */
	public function getMaxPlayers(){
		return array_sum(array_map(function (Team $team){
			return $team->getMaxPlayers();
		}, $this->getTeams()));
	}

	/**
	 * @return int
	 */
	public function getMinPlayers(){
		return array_sum(array_map(function (Team $team){
			return $team->getMinPlayers();
		}, $this->getTeams()));
	}
}