<?php


namespace xenialdan\gameapi\gamerule;


class BoolGameRule extends GameRule
{
    /** @var bool */
    public $value;

    public function __construct(string $name, bool $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function getValue(): bool
    {
        return $this->value;
    }

    /**
     * @param bool $value
     */
    public function setValue(bool $value): void
    {
        $this->value = $value;
    }

    public function getType(): int
    {
        return GameRule::TYPE_BOOL;
    }
}