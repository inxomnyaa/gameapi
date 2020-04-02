<?php

namespace xenialdan\gameapi;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use ReflectionProperty;
use Throwable;
use xenialdan\customui\elements\Input;
use xenialdan\customui\elements\Toggle;
use xenialdan\customui\windows\CustomForm;

/**
 * Class GameAPISettings
 * @package xenialdan\gameapi
 *
 * This class can NOT be extended
 */
final class GameAPISettings extends Config
{
    /** @var string[] Worlds that function as lobby. If this is empty, or the worlds can not be found, the game will generate a world "game_lobby" and use it */
    public $lobbies = ["game_lobby"];
    /** @var int Default 0 (disabled) - The amount of players that can be in a lobby. Will be ignored if there is only 1 lobby */
    public $lobbyPlayerLimit = 0;
    /** @var int Default 0 (disabled) - Radius of chunks centered around the lobby spawn that will always be loaded. Whilst this makes teleporting to the lobby faster, it increases memory usage by alot. Maximum is 15 (~500x500 blocks). */
    public $lobbyChunkLoaderRadius = 0;
    /** @var bool Default true - Protects the world from block changes done by players */
    public $lobbyProtectWorld = true;
    /** @var bool Default true - Players will not take any damage in the lobby */
    public $lobbyProtectPlayers = true;
    /** @var bool Default false - Disallow damaging entities. Enabling might cause Slapper not to function properly */
    public $lobbyProtectEntites = false;
    /** @var int Default ADVENTURE (2) - The ingame-gamemode */
    public $lobbyGamemode = Player::ADVENTURE;
    /** @var int Default NOON (6000) - Locks the time at the specified number. If set to -1, the day/night circle will continue normally */
    public $lobbyTime = 6000;
    /** @var bool Default false - Allow flying in the lobby */
    public $lobbyAllowFlight = false;
    /** @var bool Default true - Clear inventory when joining a lobby. This does not affect the inventory contents in other worlds */
    public $lobbyClearInventory = true;
    /** @var bool Default true - Stop inventory editing */
    public $lobbyStaticInventory = true;
    /** @var bool Default true - Teleport players back to the lobby spawn when falling into the void */
    public $lobbyVoidRespawn = true;
    /** @var bool Default "" - If set, this will overwrite all lobby names */
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

    public function getUI(): CustomForm
    {
        $form = new CustomForm("Game settings");
        $keys = $this->getAll(true);
        $types = [];
        foreach ($keys as $key) {
            $value = $this->$key;
            try {
                $reflectionProperty = new ReflectionProperty(self::class, $key);
                $docComment = ($reflectionProperty)->getDocComment();
                preg_match_all('/\/\*\*\s+@var\s(.+)\s\*\//m', $docComment, $parseDocComment, PREG_SET_ORDER);
                if (isset($parseDocComment[0][1])) {
                    [$type, $doc] = explode(" ", $parseDocComment[0][1], 2);
                } else {
                    $type = $reflectionProperty->getType()->getName();
                    $doc = trim($docComment, '/* \t\n\r\0\x0B');
                }
                if ($type === "bool") {
                    $form->addElement(new Toggle(TextFormat::RED . "$type " . $key . "\n" . TextFormat::AQUA . TextFormat::ITALIC . $doc, $value));
                } else if (is_array($value)) {
                    $color = stripos($type, "int") ? TextFormat::BLUE : TextFormat::GREEN;
                    $form->addElement(new Input($color . "$type " . $key . "\n" . TextFormat::AQUA . TextFormat::ITALIC . $doc, $color . "$type " . $key, implode(",", $value)));
                } else {
                    $color = $type === "int" ? TextFormat::BLUE : TextFormat::GREEN;
                    $form->addElement(new Input($color . "$type " . $key . "\n" . TextFormat::AQUA . TextFormat::ITALIC . $doc, $color . "$type " . $key, (string)$value));
                }
                $types[$key] = $type;
            } catch (Throwable $e) {
            }
        }
        $form->setCallable(function (Player $player, array $data) use ($keys, $types): void {
            foreach ($data as $i => $datum) {
                $property = $keys[$i];
                #$oldValue = $this->{$property};
                $type = $types[$property];
                if ($type === "string") {
                    $this->$property = (string)$datum;
                } else if ($type === "int") {
                    $this->$property = (int)$datum;
                } else if ($type === "bool") {
                    $this->$property = (bool)$datum;
                } else if ($type === "array" || $type === "string[]") {
                    $values = explode(",", $datum);
                    $this->$property = $values;
                } else if ($type === "int[]") {
                    $values = explode(",", $datum);
                    array_walk($values, function ($v): int {
                        return (int)$v;
                    });
                    $this->$property = $values;
                }
            }
            $this->save();
        });
        return $form;
    }
}