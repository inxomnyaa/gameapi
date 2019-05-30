<?php

declare(strict_types=1);

namespace xenialdan\gameapi\task;

use pocketmine\level\Level;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use xenialdan\gameapi\API;
use xenialdan\gameapi\Arena;
use xenialdan\gameapi\Game;

class ArenaAsyncCopyTask extends AsyncTask
{

    /** @var string */
    private $path;
    /** @var string */
    private $path2;
    /** @var string */
    private $levelname;
    /** @var string */
    private $gamename;

    /**
     * @param string $path
     * @param string $path2
     * @param string $levelname
     * @param string $gamename
     */
    public function __construct(string $path, string $path2, string $levelname, string $gamename)
    {
        $this->path = $path;
        $this->path2 = $path2;
        $this->levelname = $levelname;
        $this->gamename = $gamename;
    }

    public function onRun()
    {
        try {
            if (!API::copyr($this->path . "worlds/" . $this->levelname, $this->path2 . "worlds/" . $this->levelname)) throw new \Exception("Could not copy");
        } catch (\Throwable $e) {

        }
    }

    public function onCompletion(Server $server)
    {
        if (Server::getInstance()->loadLevel($this->levelname)) {
            Server::getInstance()->getLogger()->notice('Level ' . $this->levelname . ' successfully reloaded!');
            /** @var Game $game */
            $game = $server->getPluginManager()->getPlugin($this->gamename);
            $level = Server::getInstance()->getLevelByName($this->levelname);
            $arena = $game->getNewArena($game->getDataFolder() . $this->levelname . ".json");
            if ($arena instanceof Arena) {
                Server::getInstance()->getLogger()->notice('Arena ' . $this->levelname . ' successfully reloaded!');
                $arena->setLevel($level);
                var_dump("Is Level", $arena->getLevel() instanceof Level);
                $arena->setState(Arena::IDLE);
                $game->addArena($arena);
            }
        } else {
            if (($arena = API::getArenaByLevel(null, $server->getLevelByName($this->levelname))) instanceof Arena) {
                Server::getInstance()->broadcastMessage('Level ' . $this->levelname . ' could not be reloaded, disabling arena ' . $this->levelname . ' for the game ' . $arena->getOwningGame()->getPrefix());
                $arena->getOwningGame()->removeArena($arena);
            }
            return;
        }
    }
}
