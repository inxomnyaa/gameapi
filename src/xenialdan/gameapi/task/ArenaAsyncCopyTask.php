<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace xenialdan\gameapi\task;

use pocketmine\scheduler\AsyncTask;
use xenialdan\gameapi\API;

class ArenaAsyncCopyTask extends AsyncTask{

	/** @var string */
	private $path;
	/** @var string */
	private $path2;
	/** @var string */
	private $levelname;

	/**
	 * @param string $path
	 * @param string $path2
	 * @param string $levelname
	 */
	public function __construct(string $path, string $path2, string $levelname){
		$this->path = $path;
		$this->path2 = $path2;
		$this->levelname = $levelname;
	}

	public function onRun(){
		try{
			if(!API::copyr($this->path . "worlds/" . $this->levelname, $this->path2 . "worlds/" . $this->levelname)) throw new \Exception("Could not copy");
		} catch (\Throwable $e){

		}
	}
}
