<?php

namespace xenialdan\gameapi;

use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\utils\Config;

/**
 * Class GameAPISettings
 * @package xenialdan\gameapi
 *
 * This class can NOT be extended
 */
final class GameAPISettings extends Config
{
    /** @var string[] Worlds that function as lobby. If this is empty, or the worlds can not be found, the game will generate a world "game_lobby" and use it */
    public $lobbies = [];
    /** @var bool Default 0 (disabled) - Radius of chunks centered around the lobby spawn that will always be loaded. Whilst this makes teleporting to the lobby faster, it increases memory usage by alot. Maximum is 15 (~500x500 blocks). */
    public $lobbyChunkLoaderRadius = 0;
    /** @var bool Default true - Protects the world from block changes done by players */
    public $lobbyProtectWorld = true;
    /** @var bool Default true - Players will not take any damage in the lobby */
    public $lobbyProtectPlayers = true;
    /** @var bool Default false - Disallow damaging entities. Enabling might cause Slapper not to function properly */
    public $lobbyProtectEntites = false;
    /** @var int Default ADVENTURE (2) - The ingame-gamemode */
    public $lobbyGamemode = Player::ADVENTURE;
    /** @var int Default Level::TIME_DAY (0) - Locks the time at the specified number. If set to -1, the day/night circle will continue normally */
    public $lobbyTime = Level::TIME_DAY;
    /** @var bool Default false - Allow flying in the lobby */
    public $lobbyAllowFlight = false;
    /** @var bool Default true - Clear inventory when joining a lobby. This does not affect the inventory contents in other worlds */
    public $lobbyClearInventory = true;
    /** @var bool Default true - Stop inventory editing */
    public $lobbyStaticInventory = true;
    /** @var bool Default true - Teleport players back to the lobby spawn when falling into the void */
    public $lobbyVoidRespawn = true;
    /** @var bool Default "" - If set, this will overwrite the lobby name */
    public $lobbyName = "";
    /** @var bool Default "" - If set, this will overwrite the look of the chat messages */
    public $lobbyChatFormat = "";
    /** @var bool Default false - Separates the chat from the global server chat. Players can still write in global by prefixing the text with !g and send private messages to each other */
    public $dedicatedGameChat = false;
    /** @var bool Default true - Separates the spectator chat so spectating players can only message other spectators */
    public $dedicatedSpectatorChat = true;
    /** @var bool Default false - If true, every lobby has a dedicated chat (messages will only be seen in the lobby world) */
    public $perLobbyChat = false;
    /** @var bool Default true - Allow private messages when in a game or a lobby */
    public $allowPrivateMessages = true;
    /** @var bool Default true - Allow private messages when in a game or a lobby */
    public $allow = true;

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