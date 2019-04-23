<?php


namespace xenialdan\gameapi;


use pocketmine\block\BlockIds;
use pocketmine\level\biome\Biome;
use pocketmine\level\ChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\level\generator\Generator;
use pocketmine\math\Vector3;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Random;

class GeneratorGameVoid extends Generator
{
    /** @var Chunk */
    private $chunk;
    /** @var int */
    private $floorLevel = 0;
    /** @var int */
    private $biome;
    /** @var mixed[] */
    private $options;

    public function getSettings(): array
    {
        return $this->options;
    }

    public function getName(): string
    {
        return "game_void";
    }

    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->biome = Biome::PLAINS;
    }

    protected function generateBaseChunk(): void
    {
        $this->chunk = new Chunk(0, 0);
        $this->chunk->setGenerated();

        for ($Z = 0; $Z < 16; ++$Z) {
            for ($X = 0; $X < 16; ++$X) {
                $this->chunk->setBiomeId($X, $Z, $this->biome);
            }
        }
    }

    public function init(ChunkManager $level, Random $random): void
    {
        parent::init($level, $random);
        $this->generateBaseChunk();
    }

    public function generateChunk(int $chunkX, int $chunkZ): void
    {
        $chunk = clone $this->chunk;
        $chunk->setX($chunkX);
        $chunk->setZ($chunkZ);
        if ($chunkX === 0 && $chunkZ === 0) {
            MainLogger::getLogger()->debug("Chunk 0;0 platform");
            #$chunk->setGenerated(false);
            for ($Z = 0; $Z < 16; ++$Z) {
                for ($X = 0; $X < 16; ++$X) {
                    $this->chunk->setBlock($X, $this->floorLevel, $Z, BlockIds::STONE);
                }
            }
            #$chunk->setGenerated();
        }
        $this->level->setChunk($chunkX, $chunkZ, $chunk);
    }

    public function getSpawn(): Vector3
    {
        return new Vector3(0, $this->floorLevel + 1, 0);
    }

    public function populateChunk(int $chunkX, int $chunkZ): void
    {
    }
}