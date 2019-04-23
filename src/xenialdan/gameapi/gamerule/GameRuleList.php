<?php


namespace xenialdan\gameapi\gamerule;


class GameRuleList
{
    const COMMANDBLOCKSENABLED = "commandblocksenabled";
    const COMMANDBLOCKOUTPUT = "commandblockoutput";
    const DODAYLIGHTCYCLE = "dodaylightcycle";
    const DOENTITYDROPS = "doentitydrops";
    const DOFIRETICK = "dofiretick";
    const DOIMMEDIATERESPAWN = "doimmediaterespawn";
    const DOINSOMNIA = "doinsomnia";
    const DOMOBLOOT = "domobloot";
    const DOMOBSPAWNING = "domobspawning";
    const DOTILEDROPS = "dotiledrops";
    const DOWEATHERCYCLE = "doweathercycle";
    const DROWNINGDAMAGE = "drowningdamage";
    const EXPERIMENTALGAMEPLAY = "experimentalgameplay";
    const FALLDAMAGE = "falldamage";
    const FIREDAMAGE = "firedamage";
    const FUNCTIONCOMMANDLIMIT = "functioncommandlimit";
    const KEEPINVENTORY = "keepinventory";
    const MAXCOMMANDCHAINLENGTH = "maxcommandchainlength";
    const MOBGRIEFING = "mobgriefing";
    const NATURALREGENERATION = "naturalregeneration";
    const PVP = "pvp";
    const RANDOMTICKSPEED = "randomtickspeed";
    const SENDCOMMANDFEEDBACK = "sendcommandfeedback";
    const SHOWCOORDINATES = "showcoordinates";
    const SHOWDEATHMESSAGES = "showdeathmessages";
    const TNTEXPLODES = "tntexplodes";


    public function __construct()
    {
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return (array)$this;
    }

    public function setRule(GameRule $rule): void
    {
        $name = $rule->getName();
        $this->$name = [$rule->getType(), $rule->getValue()];
    }

}