<?php

namespace xenialdan\gameapi;

use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use xenialdan\BossBarAPI\API as BossBarAPI;
use xenialdan\gameapi\event\UpdateSignsEvent;
use xenialdan\gameapi\event\WinEvent;
use xenialdan\gameapi\task\StartTickerTask;

define('DS', DIRECTORY_SEPARATOR);

class Arena
{
    const IDLE = 0;
    const WAITING = 1;
    const STARTING = 2;
    const INGAME = 3;
    const STOP = 4;
    const SETUP = 5;

    const TIMER_MAX = 30;
    /** @var Game|Plugin */
    private $owningGame;
    /** @var int */
    private $timer;
    /** @var TaskHandler[] */
    private static $tasks;
    public $state = self::IDLE;
    private $levelName;
    private $level = null;
    /** @var Team[] */
    private $teams;
    /** @var int[] */
    public $bossbarids = [];
    /** @var DefaultSettings */
    public $settings;
    public $forcedStart = false;

    /**
     * Arena constructor.
     * @param string $levelName
     * @param Plugin $game
     * @param DefaultSettings|null $settings
     */

    public function __construct(string $levelName, Plugin $game, DefaultSettings $settings = null)
    {
        $this->owningGame = $game;
        $this->levelName = $levelName;
        try {
            Server::getInstance()->generateLevel($levelName, null, GeneratorManager::getGenerator('game_void'));
            //reset world
            $path1 = $this->owningGame->getDataFolder() . "worlds" . DS;
            @mkdir($path1);

            if (!API::copyr($this->owningGame->getServer()->getDataPath() . "worlds" . DS . $levelName, $path1 . $levelName)) {
                throw new MiniGameException('Could not copy level to plugin..');
            }
            Server::getInstance()->loadLevel($levelName);
            $this->level = Server::getInstance()->getLevelByName($levelName);
            $this->setSettings($settings);
            //Prevents changing the level
            #$this->getLevel()->setAutoSave(false);
        } catch (MiniGameException $exception) {
            Server::getInstance()->getLogger()->logException($exception);
        }
    }

    /**
     * @return Plugin|Game
     */
    public function getOwningGame(): Game
    {
        return $this->owningGame;
    }

    /**
     * Adds a Team
     * @param Team $team
     */
    public function addTeam(Team $team)
    {
        $this->teams[] = $team;
    }

    /**
     * Returns the level for the arena
     * @return Level
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param Level $level
     */
    public function setLevel(Level $level)
    {
        $this->level = $level;
    }

    /**
     * Returns the name of the level for the arena
     * @return string
     */
    public function getLevelName()
    {
        return $this->levelName;
    }

    /**
     * Returns if the player is in this arena
     * @param $player
     * @return bool
     */
    public function inArena($player): bool
    {
        return !is_null($this->getTeamByPlayer($player));
    }

    /**
     * Returns the Team a player is in, or none
     * @param Player $player
     * @return null|Team
     */
    public function getTeamByPlayer(Player $player): ?Team
    {
        foreach ($this->getTeams() as $team) {
            if ($team->inTeam($player)) return $team;
        }
        return null;
    }

    /**
     * Returns the Team a player is in, or none
     * @param string $color
     * @return null|Team
     */
    public function getTeamByColor(string $color): ?Team
    {
        foreach ($this->getTeams() as $team) {
            if ($team->getColor() === $color) return $team;
        }
        return null;
    }

    /**
     * Returns all teams
     * @return Team[]
     */
    public function getTeams()
    {
        return $this->teams;
    }

    /**
     * Adds a player to a team in the arena and updates its state
     * Calls onPlayerJoinGame() upon successfully joining a team/game
     * If $teamname is given, the player will forcefully join a specific team,
     * regardless of the arena already being ingame or not
     * @param Player $player
     * @param string $teamname
     * @return bool
     */
    public function joinTeam(Player $player, string $teamname = "")
    {
        if ($this->getState() === self::SETUP) {
            $player->sendMessage(TextFormat::RED . TextFormat::BOLD . "This arena is currently not available due to setup process");
            return false;
        }
        if (($this->getState() === self::INGAME && count($this->getPlayers()) <= 0) || $this->getState() === self::STOP) {
            $player->sendMessage(TextFormat::RED . TextFormat::BOLD . "This arena did not stop properly");
            $player->sendMessage(TextFormat::RED . TextFormat::BOLD . "An issue occurred, trying to stop the arena. Please try again in a few seconds.");
            API::resetArena($this);//also automatically stops the arena and removes players
            return false;
        }
        if (($this->getState() === self::INGAME) && empty($teamname) && is_null(API::getArenaOfPlayer($player))) {
            $player->sendMessage(TextFormat::RED . TextFormat::BOLD . "This arena is already running");
            return false;
        }
        if (!is_null(($oldteam = $this->getTeamByPlayer($player)))) {
            $oldteam->removePlayer($player);
        }
        if (empty($teamname)) {
            /** @var Team $team */
            $count = [];
            foreach ($this->getTeams() as $team) {
                if (count($team->getPlayers()) < $team->getMaxPlayers())
                    $count[$team->getName()] = count($team->getPlayers());
            }
            if (!empty($count)) $team = $this->getTeamByName($teamname = array_keys($count, min($count))[0]);
        } elseif (!($team = $this->getTeamByName($teamname)) instanceof Team) {
            $player->sendMessage(TextFormat::RED . TextFormat::BOLD . "Could not join team $teamname, Reason: A team with this name does not exist");
            return false;
        }
        if (is_null($team) || count($team->getPlayers()) >= $team->getMaxPlayers()) {
            $player->sendMessage(TextFormat::RED . TextFormat::BOLD . "Could not join team $teamname, Reason: This team is full");
            return false;
        }
        if ($this->getState() === self::WAITING || $this->getState() === self::STARTING || $this->getState() === self::IDLE) {
            var_dump(__FILE__ . __LINE__, (string)$this->getLevel()->getSafeSpawn());
            $player->teleport(Position::fromObject($this->getLevel()->getSafeSpawn()->add(0, 1), $this->getLevel()));
            Server::getInstance()->getLogger()->notice($player->getName() . ' added to ' . $teamname);
            $gamename = $this->owningGame->getPrefix();
            if (!isset($this->bossbarids['state'])) {
                $this->bossbarids['state'] = BossBarAPI::addBossBar([$player], $gamename . " Waiting for players..");
            }
            BossBarAPI::sendBossBarToPlayer($player, $this->bossbarids['state'], $gamename . " Waiting for players..");
            if (count($this->getPlayers()) < $this->getMinPlayers()) Server::getInstance()->broadcastMessage(TextFormat::RED . TextFormat::BOLD . "The game " . $gamename . " needs players!", Server::getInstance()->getDefaultLevel()->getPlayers());
            elseif (count($this->getPlayers()) < $this->getMaxPlayers()) Server::getInstance()->broadcastMessage(TextFormat::DARK_GRAY . TextFormat::BOLD . "The game " . $gamename . " is not full, you can still join it!", Server::getInstance()->getDefaultLevel()->getPlayers());
            BossBarAPI::setTitle($gamename . " Waiting for players.. " . count($this->getPlayers()) . '/' . $this->getMinPlayers() . '-' . $this->getMaxPlayers(), $this->bossbarids['state'], $this->getPlayers());

            $this->setState(self::WAITING);
        } else var_dump($this->getLevelName(), "state: " . $this->state);//TODO remove debug
        $team->addPlayer($player);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getEnderChestInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $this->owningGame->onPlayerJoinGame($player);
        $player->sendMessage($team->getColor() . TextFormat::BOLD . "You joined the team " . $team->getName());
        $player->setGamemode(Player::ADVENTURE);
        if (($this->getState() === self::WAITING || $this->getState() === self::IDLE || $this->getState() === self::STARTING) && count($this->getPlayers()) >= $this->getMinPlayers()) {
            $this->setState(self::STARTING);
            if (isset(self::$tasks['ticker'])) $this->resetTimer();
            $this->startTimer($this->owningGame);
        }
        return true;
    }

    /**
     * @return Player[]
     */
    public function getPlayers()
    {
        $players = [];
        foreach ($this->getTeams() as $team) {
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
    public function getTeamByName(string $teamname)
    {
        foreach ($this->getTeams() as $team) {
            if ($team->getName() === $teamname) return $team;
        }
        return null;
    }

    /**
     * @param int $timer
     */
    public function setTimer(int $timer): void
    {
        $this->timer = $timer;
    }

    /**
     * @param Plugin|Game $game
     */
    public function startTimer(Game $game)
    {
        $this->resetTimer();
        $this->setState(self::STARTING);
        self::$tasks['ticker'] = $game->getScheduler()->scheduleRepeatingTask(new StartTickerTask($game, $this), 20);
    }

    /**
     * @param Plugin|Game $game
     */
    public function sendTimer(Game $game)
    {
        if (!$this->forcedStart && count($this->getPlayers()) < $this->getMinPlayers()) {
            Server::getInstance()->broadcastTitle(TextFormat::DARK_RED . "Too less players", "for " . $game->getPrefix() . ', ' . $this->getMinPlayers() . ' players are needed!');
            $this->resetTimer();
            return;
        }
        BossBarAPI::setTitle('Game ' . $game->getPrefix() . ' starts in ' . $this->timer . ' seconds', $this->bossbarids['state'], $this->getPlayers());
        BossBarAPI::setPercentage($this->timer / self::TIMER_MAX * 100, $this->bossbarids['state'], $this->getPlayers());
        $this->timer--;
        if ($this->timer < 0) {
            $this->resetTimer();
            $this->startArena();
        }
    }

    public function resetTimer()
    {
        $this->forcedStart = false;
        $this->setState(self::WAITING);
        if (isset(self::$tasks['ticker'])) $this->getOwningGame()->getScheduler()->cancelTask(self::$tasks['ticker']->getTaskId());
        unset(self::$tasks['ticker']);
        $this->timer = self::TIMER_MAX;
    }

    public function startArena()
    {
        foreach ($this->getTeams() as $team) {
            $team->resetInitialPlayers();
            $team->updateInitialPlayers();
        }
        $this->setState(self::INGAME);
        $this->getOwningGame()->startArena($this);
    }

    public function stopArena()
    {
        foreach ($this->getPlayers() as $player) {
            $this->removePlayer($player);
        }
        $this->setState(self::STOP);
        foreach ($this->getTeams() as $team) {
            $team->resetInitialPlayers();
        }
        $this->getOwningGame()->stopArena($this);
    }

    /**
     * @param int $state
     */
    public function setState(int $state)
    {
        $this->state = $state;
        $ev = new UpdateSignsEvent($this->getOwningGame(), [$this->getOwningGame()->getServer()->getDefaultLevel()], $this);
        try {
            $ev->call();
        } catch (\ReflectionException $e) {
            Server::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @param Player $player
     * @throws \ReflectionException
     */
    public function removePlayer(Player $player)
    {
        $team = $this->getTeamByPlayer($player);
        var_dump($team);
        if ($team instanceof Team) {
            $team->removePlayer($player);
        }
        $player->setSpawn(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
        Server::getInstance()->getLogger()->notice($player->getName() . ' removed');
        if ($player->isOnline()) {
            if (isset($this->bossbarids['state'])) BossBarAPI::removeBossBar([$player], $this->bossbarids['state']);
            $player->setNameTag($player->getDisplayName());
            $player->getInventory()->clearAll();
            $player->setGamemode(Server::getInstance()->getDefaultGamemode());
            $player->setHealth($player->getMaxHealth());
            $player->setFood($player->getMaxFood());
            $player->removeAllEffects();
            $player->setAllowFlight(false);
            $player->setDataFlag(Player::DATA_FLAGS, Player::DATA_FLAG_IMMOBILE, false);
            $player->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
            $player->sendSettings();
        }
        $aliveTeams = array_filter($this->getTeams(), function (Team $team): bool {
            return count($team->getPlayers()) > 0;
        });
        $aliveTeamsCount = count($aliveTeams);
        if (($aliveTeamsCount === 1 && count($this->teams) > 1) || (count($this->teams) === 1 && count($team->getPlayers()) === 1) || ($this->getState() !== self::STOP && count($this->getPlayers()) === 0)) {
            if (count($this->getPlayers()) !== 0) {
                if (count($this->teams) > 1) {
                    $winner = array_values($aliveTeams)[0];
                } else {
                    $winner = array_values($team->getPlayers())[0];
                }
                $ev = new WinEvent($this->getOwningGame(), $this, $winner);
                $ev->call();
                $ev->announce();
            }
            if ($this->getState() === self::INGAME && count($this->getPlayers()) === 0) $this->setState(self::IDLE);
            if ($this->getState() === self::INGAME) $this->getOwningGame()->getScheduler()->scheduleDelayedTask(new class($this) extends Task
            {
                /** @var Arena */
                private $arena;

                /**
                 * @param Arena $arena
                 */
                public function __construct(Arena $arena)
                {
                    $this->arena = $arena;
                }


                /**
                 * Actions to execute when run
                 *
                 * @param int $currentTick
                 *
                 * @return void
                 */
                public function onRun(int $currentTick)
                {
                    API::resetArena($this->arena);
                }
            }, 5 * 20);
        }
    }

    /**
     * @return int
     */
    public function getMaxPlayers()
    {
        return array_sum(array_map(function (Team $team) {
            return $team->getMaxPlayers();
        }, $this->getTeams()));
    }

    /**
     * @return int
     */
    public function getMinPlayers()
    {
        return array_sum(array_map(function (Team $team) {
            return $team->getMinPlayers();
        }, $this->getTeams()));
    }

    /**
     * @return DefaultSettings|null
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param DefaultSettings|null $settings
     */
    public function setSettings(?DefaultSettings $settings)
    {
        $this->settings = $settings;
    }
}
