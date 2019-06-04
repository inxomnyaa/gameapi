<?php


namespace xenialdan\gameapi;

use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\utils\Config;

/**
 * Class DefaultSettings
 * @package xenialdan\gameapi
 *
 * This class can be extended as you'd like
 *
 * For serialisation / saving to file you should only use objects
 * that are convertible to basic types (arrays, bool, int, string),
 * but DO NOT try to serialize complex objects.
 *
 * Remember to also use uuid or player names instead of the player object!
 */
class DefaultSettings extends Config
{
    /** @var bool $immutableWorld Default true - Stops all world building/placing actions & interactions. WILL OVERRIDE $noBuild and $noBreak */
    public $immutableWorld = true;
    /** @var int $gamemode Default SURVIVAL (0) - The ingame-gamemode */
    public $gamemode = Player::SURVIVAL;
    /**
     * @var bool $startNoWalk
     * Default true - Stops the player from walking (but not rotating)
     * from the current position when the game starts
     */
    public $startNoWalk = true;
    /** @var bool $stopTime Default true - The time will not change */
    public $stopTime = true;
    /** @var int $time Default Level::TIME_DAY - The time of the Level upon start */
    public $time = Level::TIME_DAY;
    /** @var bool $noBed Default true - Stops players from using Beds */
    public $noBed = true;
    /** @var bool $noBuild Default false - Stops players from placing blocks */
    public $noBuild = false;
    /** @var bool $noBreak Default false - Stops players from breaking blocks */
    public $noBreak = false;
    /** @var bool $noPickup Default false - Disallows picking up items */
    public $noPickup = false;
    /** @var bool $noEntityDrops Default true - Stops item drops from entities */
    public $noEntityDrops = true;
    /** @var bool $noDropItem Default true - Stops players from dropping items */
    public $noDropItem = true;
    /** @var bool $noBlockDrops Default true - Stops item drops from blocks */
    public $noBlockDrops = true;
    /** @var bool $keepInventory Default false - Keep inventory upon death */
    public $keepInventory = false;
    /** @var bool $clearInventory Default false - Clear inventory upon death. WILL OVERRIDE $keepInventory */
    public $clearInventory = false;
    /** @var bool $noArrowPickup Default false - Stops arrows from being picked up (behaving like "creative" arrows) */
    public $noArrowPickup = false;
    /** @var bool $noDamageEntities Default true - Disallow damaging entities (except players) */
    public $noDamageEntities = true;
    /** @var bool $noDamageTeam Default true - Players can not damage team members */
    public $noDamageTeam = true;
    /** @var bool $noDamageEnemies Default false - Enemies (Players from other teams) can not be damaged */
    public $noDamageEnemies = false;
    /** @var bool $noEnvironmentDamage Default false - Players will not get damage from cacti, fire, magma blocks etc. */
    public $noEnvironmentDamage = false;
    /** @var bool $noFallDamage Default false - Players will not get damage from falling */
    public $noFallDamage = false;
    /** @var bool $noExplosionDamage Default false - Players will not get damage from entity or block explosions */
    public $noExplosionDamage = false;
    /** @var bool $noDrowningDamage Default false - Players will not get damage from drowning */
    public $noDrowningDamage = false;
    /** @var bool $noInventoryEditing Default false - Stop inventory editing */
    public $noInventoryEditing = false;
    /** @var bool $allowFlight Default false - Allow flying */
    public $allowFlight = false;
    /** @var array $breakBlockIds Default [] - Breakable blocks. WILL OVERRIDE $noBreak and $immutableWorld */
    public $breakBlockIds = [];
    /** @var array $placeBlockIds Default [] - Placeable blocks. WILL OVERRIDE $noPlace and $immutableWorld */
    public $placeBlockIds = [];
    /** @var array containing team spawns, color and name */
    public $teams = [];

    public function __construct(string $path)
    {
        parent::__construct($path, Config::JSON, array_filter((array)$this, function ($k): bool {
            return strpos($k, Config::class) === false;
        }, ARRAY_FILTER_USE_KEY));
        foreach ($this->getAll(true) as $key) {
            if (isset($this->$key)) $this->$key = $this->get($key);
        }
    }

    public function __set($k, $v)
    {
        $this->$k = $v;
        $this->set($k, $v);
    }

    public function __get($k)
    {
        return $this->get($k);
    }

    public function save(): bool
    {
        $this->setAll(
            array_filter((array)$this, function ($k): bool {
                return strpos($k, Config::class) === false;
            }, ARRAY_FILTER_USE_KEY)
        );
        return parent::save();
    }
}