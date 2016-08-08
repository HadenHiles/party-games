<?php

class DrinkOrDare {

    private $state;
    private $gameid;
    private $total_rounds;
    private $current_round;
    private $userid;
    private $hasCurrentDare;
    private $drinksToWin;

    public function __construct($game_id = 0, $userid = 0, $total_rounds = 3, $current_round = 1, $drinksToWin = 10) {

        $this->gameid = $game_id;
        $this->state = 1;
        $this->total_rounds = $total_rounds;
        $this->current_round = $current_round;
        $this->userid = $userid;
        $this->hasCurrentDare = false;
        $this->drinksToWin = $drinksToWin;
    }

    /**
     * @return array|bool
     */
    public function getDrinkOrDare() {
        global $db;

        $sql = 'SELECT * FROM drink_or_dare WHERE game_id = :gameid';

        $result = $db->prepare($sql);
        $result->bindParam(":gameid", $this->gameid);

        if ($result->execute() && $result->errorCode() == 0) {
            if ($result->rowCount() > 0) {
                return $result->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        return false;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function start() {

        global $db;

        if (!empty($this->gameid)) {
            if (!$this->isStarted($this->gameid)) {
                //game doesnt exist
                $sql = 'INSERT INTO drink_or_dare
                        (game_id, state, total_rounds, current_round, drinks_to_win) 
                        VALUES
                        (:gameid, :state, :total_rounds, :current_round, :drinks_to_win)';

                $result = $db->prepare($sql);
                $result->bindParam(":gameid", $this->gameid);
                $result->bindParam(":state", $this->state);
                $result->bindParam(":total_rounds", $this->total_rounds);
                $result->bindParam(":current_round", $this->current_round);
                $result->bindParam(":drinks_to_win", $this->drinksToWin);

                if ($result->execute() && $result->errorCode() == 0) {
                    return true;
                }
            } else {
                //game exists
                $sql = 'SELECT * FROM drink_or_dare WHERE game_id = :gameid';

                $result = $db->prepare($sql);
                $result->bindParam(":gameid", $gameId);

                if ($result->execute() && $result->errorCode() == 0) {
                    if ($result->rowCount() > 0) {
                        $result = $result->fetch(PDO::FETCH_ASSOC);

                        $this->state = $result['state'];
                        $this->total_rounds = $result['total_rounds'];
                        $this->current_round = $result['current_round'];
                        $this->drinksToWin = $result['drinks_to_win'];

                        //get user dare state
                        if (!empty($this->gameid)) {

                            $sql = 'SELECT * FROM drink_or_dare_user_dares 
                            WHERE user_id = :userid 
                            AND round_number = :round_number';

                            $result = $db->prepare($sql);
                            $result->bindParam(":userid", $this->userid);
                            $result->bindParam(":round_number", $this->current_round);

                            if ($result->execute() && $result->errorCode() == 0 && $result->rowCount() > 0) {

                                $this->hasCurrentDare = true;
                            }
                        }

                        return true;
                    }
                }
            }
        } else {
            throw new Exception("Cannot load game without game id.");
        }

        return false;
    }

    /**
     * @param $gameId
     * @return bool
     */
    public function isStarted($gameId) {
        global $db;

        $sql = 'SELECT * FROM drink_or_dare WHERE game_id = :gameid';

        $result = $db->prepare($sql);
        $result->bindParam(":gameid", $gameId);

        if ($result->execute() && $result->errorCode() == 0) {
            if ($result->rowCount() > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $gameId
     * @param $state
     * @param $totalRounds
     * @param $currentRound
     * @param $drinksToWin
     * @return bool
     */
    public function update($gameId, $state, $totalRounds, $currentRound, $drinksToWin) {
        global $db;

        $sql = 'UPDATE drink_or_dare SET 
                  state = :state, 
                  total_rounds = :total_rounds, 
                  current_round = :current_round, 
                  drinks_to_win = :drinks_to_win
                WHERE game_id = :gameid';

        $result = $db->prepare($sql);
        $result->bindParam(":gameid", $gameId);
        $result->bindParam(":state", $state);
        $result->bindParam(":total_rounds", $totalRounds);
        $result->bindParam(":current_round", $currentRound);
        $result->bindParam(":drinks_to_win", $drinksToWin);

        if ($result->execute() && $result->errorCode() == 0) {
            return true;
        }
        return false;
    }

    public function setDare($text) {

        global $db;

        if (!empty($this->gameid)) {

            $sql = 'SELECT * FROM drink_or_dare_user_dares 
                    WHERE user_id = :userid 
                    AND round_number = :round_number';

            $result = $db->prepare($sql);
            $result->bindParam(":userid", $this->userid);
            $result->bindParam(":round_number", $this->current_round);

            if ($result->execute() && $result->errorCode() == 0 && $result->rowCount() > 0) {

            } else {

                //dare doesnt exist for this user and round
                $sql = 'INSERT INTO drink_or_dare_user_dares
                        (user_id, dare, round_number) 
                        VALUES
                        (:userid, :dare, :round_number)';

                $result = $db->prepare($sql);
                $result->bindParam(":userid", $this->userid);
                $result->bindParam(":dare", $text);
                $result->bindParam(":round_number", $this->current_round);

                if ($result->execute() && $result->errorCode() == 0) {
                    return true;
                }
            }
        } else {
            throw new Exception("Cannot set dare without game id.");
        }

        return false;
    }

    public function checkDaresComplete() {
        return false;
    }

    public function nextState() {

        global $db;

        if (!empty($this->state)) {

            if ($this->state == 1) {
                $this->state = 2;
            }

            $sql = 'UPDATE drink_or_date SET state = :state WHERE game_id = :game_id';

            $result = $db->prepare($sql);
            $result->bindParam(":game_id", $this->gameid);
            $result->bindParam(":state", $this->state);

            if ($result->execute() && $result->errorCode() == 0) {
                return true;
            }
        }
        return false;
    }

    public function getState() {

        return $this->state;
    }

    public function getHasCurrentDare() {

        return $this->hasCurrentDare;
    }

    public function getTotalRounds() {

        return $this->total_rounds;
    }

    public function getDrinksToWin() {

        return $this->drinksToWin;
    }
}
?>