<?php

include_once 'php/PingRequest.php';

//extends PingReq to rewrite methods
trait BackEnd {

    public $TURNS = array(
        "start",
        "night",
        "preDay",
        "day",
        "postDay"
    );

//TURNS
    protected function start() {
        
    }

    protected function night($initiative = null) { //1 (every night turn)
        $gameId = $this->gameId;

        //EVERY NIGHT TURN
        if (!$initiative) {
            //if comes from statuschange card action 
            $initiative = $this->getInitiative(); //utils
        }

        $cardName = $this->getNextNightTurn($initiative);

        //(can be false)
        if (!$cardName) {

            if ($initiative < -1) { //prevent dayEnd bucles if no night cards (could be error)
                $this->setGameStatus(0);
                //echo "error: no night cards?";
                return;
            }

            sql("UPDATE smltown_games SET status = 2, night = 'preDay' WHERE id = $gameId");
            $this->updateGame(null, array("night", "status"));

            $kills = $this->getDying();
            $this->saveMessage("kills:" . json_encode($kills));
            $this->updatePlayers(null, "status"); //status like 4 hunter
        //
        } else {
            sql("UPDATE smltown_games SET night = '$cardName' WHERE id = $gameId");
            $this->updateGame(null, "status");
            $this->runTurn($cardName);
        }
    }

    protected function preDay($initiative = null) { //2
        if ($this->gameStatusChange()) {
            return;
        }
        $this->killDying(); //no message
        $this->setNextStatus(-1);
    }

    protected function day($initiative = null) { //3
        $gameId = $this->gameId;

        $time = petition("SELECT time FROM smltown_games WHERE id = $gameId")[0]->time;
        if ($time > 0) {
            return;
        }

        if ($this->checkGameOver($gameId)) { //first (status = 5)
            return;
        }

        if ($this->playersAlive() == 2) { // if 2 players
            $this->saveMessage("alive2"); //alive users
            sql("UPDATE smltown_games SET night = null WHERE id = $gameId"); //set night        
            return;
        }

        //START DAY
        $now = round(microtime(true));
        $newTime = $this->getDiscusTime($now); //seconds
        sql("UPDATE smltown_games SET night = null, timeStart = $now, time = $newTime WHERE id = $gameId");
        $this->updateGame(null, array("timeStart", "time")); //updates game
    }

    protected function postDay($initiative = null) { //4
        if ($this->gameStatusChange()) {
            return;
        }
        $this->killDying(); //no message
        $this->setNextStatus(-1);
    }

///////////////////////////////////////////////////////////////////////////////
// DAY ACTIONS

    protected function townVotations() { //day end
        $gameId = $this->gameId;

        //update time
        $sth = sql("UPDATE smltown_games SET timeStart = null, time = null, night = null WHERE id = $gameId"
                . " AND (time < " . microtime(true)
                //or endTurn is enabled
                . " OR time = 0)");

        if ($sth->rowCount() == 0) {
            return; //prevent multiple dayEnd requests
        }

        $players = petition("SELECT sel FROM smltown_plays WHERE gameId = $gameId AND status > 0 AND admin != -2");
        $deadId = votations($players);

        //hurt
        if ($deadId != null) {
            $this->hurtPlayer($deadId);
        }
        //end day
        $kills = $this->getDying();
        $this->saveMessage("votations:" . json_encode($kills));
    }

////////////////////////////////////////////////////////////////////////////////
// NIGHT ACTIONS

    protected function getNextNightTurn($turn) {
        $gameId = $this->gameId;

        //alive only. distinct: not duplicated
        $plays = petition("SELECT DISTINCT card FROM smltown_plays WHERE gameId = $gameId AND status > -1 "
                . "AND card > '' AND card IS NOT NULL");

        if (0 == count($plays)) {
            return false;
        }

        $lowestInitiative = 100;
        $next = null;
        for ($i = 0; $i < count($plays); $i++) {
            $cardName = $plays[$i]->card;
            $card = $this->getCardFileByName($cardName);

            if (!isset($card->initiative)) { //like hunter
                continue;
            }
            $initiative = $card->initiative;

            if ($initiative > $turn && $initiative < $lowestInitiative) {
                $lowestInitiative = $initiative;
                $next = $cardName;
            }
        }

        return $next;
    }

}
