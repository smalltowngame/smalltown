<?php

include_once 'PingRequest.php';

class AdminRequest extends PingRequest {

//functions only for admin
// GAME ENGINE
    public function startGame() {
        $gameId = $this->gameId;

        //add villagers bots
        $countPlayers = petition("SELECT count(*) as count FROM smltown_plays WHERE gameId = $gameId AND status > -1")[0]->count;
        if (4 > $countPlayers) {
            $this->saveMessage("_botsAdded <br/> _gameWillStart");
            $sql = "INSERT INTO smltown_plays (gameId, userId, status, card, admin) VALUES";
            $minPlayers = 4;
            for ($i = $countPlayers; $i < $minPlayers; $i++) {
                $userId = getRandomUserId(); //check TODO
                $sql = "$sql ($gameId, $userId, 1, '_villager', -2)";
                if ($i + 1 < $minPlayers) {
                    $sql = "$sql,";
                }
            }
            sql($sql);
            $this->updatePlayers();
            //
        } else {
            $this->saveMessage("gameWillStart"); //second saveMessage
        }

        //start game
        sql("UPDATE smltown_games SET status = 1, night = -100 WHERE id = $gameId");
        sql("UPDATE smltown_plays SET message = '' WHERE message = NULL");
//        $this->updateGame(null, "status");        
        //RECORD
        $file = fopen("record/$gameId.txt", "a");
        fwrite($file, "Game start: players($countPlayers)");
    }

    public function restartGame() {
        $gameId = $this->gameId;

        //clean
        sql("DELETE FROM smltown_plays WHERE gameId = $gameId AND admin = -2");
        sql("UPDATE smltown_plays SET status = 1, sel = null, rulesJS = '', rulesPHP = '', reply = '', message = null WHERE gameId = $gameId");
        sql("UPDATE smltown_games SET status = 0, night = null, timeStart = null, time = null WHERE id =  $gameId");

        $players = petition("SELECT * FROM smltown_plays WHERE admin != -1 AND gameId = $gameId");

        $cards = $this->loadCards();
        if (false == $cards) {
            return false;
        }
        $playerCount = count($players);

        $quantityCards = $this->getCards($cards, $playerCount);

        $minCards = $quantityCards[0];
        $maxCards = $quantityCards[1];

        $playerKeys = range(0, $playerCount - 1);
        shuffle($playerKeys);

        //set min cards
        for ($i = 0; $i < count($minCards); $i++) {
            if (!isset($playerKeys[$i])) {
                echo "error: more cards than players";
                break;
            }
            $players[$playerKeys[$i]]->card = $minCards[$i];
        }

        $maxCardKeys = array();
        if (count($maxCards)) {
            $maxCardKeys = range(0, count($maxCards) - 1);
            shuffle($maxCardKeys);
        }
//    $maxCardKeys = range(0, count($maxCards));
//    $maxCardKeys = range(0, count($minCards) - 1);

        $n = count($minCards);
//    //set max cards
        //set min cards
        for ($i = 0; $i < count($maxCardKeys); $i++) { //by CARDS!
            if (rand(0, 1) == 1) {
                if (count($playerKeys) - $n < 1) {
                    break;
                }
                $card = $maxCardKeys[$i];
                $playerId = $playerKeys[$n];
                $players[$playerId]->card = $maxCards[$card];
//            $players[$playerId]->card = $minCards[$card];
                $n++;
            }
        }

        $playersLeft = count($playerKeys) - $n;
        for ($i = 0; $i < $playersLeft; $i++) {
            $playerId = $playerKeys[$n++];
            $players[$playerId]->card = "_villager";
        }

        //prepare "sqlUpdateCol" array
        $cardArray = array();
        for ($i = 0; $i < $playerCount; $i++) {
            $id = $players[$i]->id;
            $cardArray[$id] = $players[$i]->card;
        }

        //save user cards on DB
        $sql1 = $sql2 = "";
        foreach ($cardArray as $id => $value) {
            $sql1 = "$sql1 WHEN '$id' THEN '$value'";
            $sql2 = "$sql2 '$id'";
            end($cardArray); //http://stackoverflow.com/questions/1070244/how-to-determine-the-first-and-last-iteration-in-a-foreach-loop
            if ($id !== key($cardArray)) {
                $sql2 = "$sql2,";
            }
        }
        sql("UPDATE smltown_plays SET card = CASE id $sql1 END WHERE id IN ($sql2)");

        $this->updateUsers(null, "card");
        $this->updatePlayers(null, array("status", "sel"));
        $this->updateGame(null, "status");
    }

    public function endTurn() { //ADMIN end day time. let vote before
        $gameId = $this->gameId;
        $playId = $this->playId;

        sql("UPDATE smltown_games SET time = 0 WHERE id = $gameId"
                . " AND (SELECT admin FROM smltown_plays WHERE id = $playId) = 1");
        $this->updateGame(null, "time");
    }

////////////////////////////////////////////////////////////////////////////////
// SET MENU OPTIONS GAME

    public function setPublicGameRule() {
        $gameId = $this->gameId;
        $public = $this->requestValue['value'];

        $values = array('public' => $public);
        sql("UPDATE smltown_games SET public = :public WHERE id = $gameId", $values);
        $this->updateGame(null, "public");
        if ($public == 1) {
            $this->setFlash("_gamePublic");
        } else {
            $this->setFlash("_gameNotPublic");
        }
    }

    public function setDayTime() {
        $gameId = $this->gameId;

        $values = array('dayTime' => $obj->time);
        sql("UPDATE smltown_games SET dayTime = :dayTime WHERE id = $gameId", $values);
        $this->updateGame(null, "dayTime");
        $this->setFlash("_updatedDayTime");
    }

    public function setOpenVoting() {
        $gameId = $this->gameId;
        $openVotations = $this->requestValue['value'];

        $values = array('openVoting' => $openVotations);
        sql("UPDATE smltown_games SET openVoting = :openVoting WHERE id = $gameId", $values);
        $this->updateGame($gameId, null, "openVoting");
        if ($openVotations == 1) {
            $this->setFlash("_openVotingEnabled");
        } else {
            $this->setFlash("_openVotingDisabled");
        }
    }

    public function setEndTurnRule() {
        $gameId = $this->gameId;
        $endTurn = $this->requestValue['value'];

        $values = array('endTurn' => $endTurn);
        sql("UPDATE smltown_games SET endTurn = :endTurn WHERE id = $gameId", $values);
        $this->updateGame(null, "endTurn");
        if ($endTurn == 1) {
            $this->setFlash("_adminEndTurnEnabled");
        } else {
            $this->setFlash("_adminEndTurnDisabled");
        }
    }

    public function setPassword() {
        $gameId = $this->gameId;
        if (1 == $gameId) {
            echo "You can't set password on main server game for security reasons";
            return;
        }
        $password = $this->requestValue['password'];

        $values = array('password' => $password);
        sql("UPDATE smltown_games SET password = :password WHERE id = $gameId", $values);
        $this->updateGame(null, "password");
        if (empty($password)) {
            $this->setFlash("_passwordRemoved");
        } else {
            $this->setFlash("_passwordUpdated");
        }
    }

    public function saveCards() {
        $gameId = $this->gameId;
        $cards = $this->requestValue['cards'];

        $values = array(
            'cards' => $cards
        );
        sql("UPDATE smltown_games SET cards = :cards WHERE id = $gameId", $values);
        $this->updateGame(null, "cards");
    }

}
