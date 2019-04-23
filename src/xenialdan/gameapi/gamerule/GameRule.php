<?php


namespace xenialdan\gameapi\gamerule;


abstract class GameRule
{
    const TYPE_BOOL = 1;
    const TYPE_INT = 2;
    const TYPE_FLOAT = 3;
    public $value;
    /** @var string */
    public $name;

    /**
     * @return mixed
     */
    abstract public function getValue();

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    abstract public function getType(): int;
}