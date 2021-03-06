<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/includes/common.php');

require_once(ROOT.'/includes/database.php');
require_once(ROOT.'/includes/class.GameSession.php');
require_once(ROOT.'/includes/class.User.php');
require_once(ROOT.'/games/drink-or-dare/class.DrinkOrDare.php');

$pageTitle = 'Playing Drink Or Dare';

try {
    //init classes
    $mySession = new GameSession(SESSION_ID, DEVICE_IP);
    $user = new User(SESSION_ID, DEVICE_IP);

    //check for user in session
    if (empty($_SESSION['user'])) {
        $_SESSION['user'] = $user->getUser();
        header("Location: /join/");
        exit();
    }

    //check we have valid code
    if (!$mySession->validateGame($_SESSION['game']['code'])) {
        header("Location: /join/");
        exit();
    }

    $thisUser = $_SESSION['user'];
    $code = $_SESSION['game']['code'];
    $isHost = $user->isHost("get", $thisUser['id']);
    $isDisplay = $user->isDisplay("get", $thisUser['id']);
    //var_dump($thisUser);

    if (!$game = $mySession->loadUsers($code, 0)) {
        //game was not found
        $msg[] = array("msg" => "game-not-found", "popup" => "dialog");
        header("Location: /join/?unique-id=".$code);
        exit();
    } else {

        //init the new game session
        $dod = new DrinkOrDare($code, $thisUser['id']);
        if ($result = $dod->start()) {
            //start game in game_conenctions table which triggers others to join game
            $mySession->setCode($code);
            $mySession->start();
        }

        $roundNum = $dod->getCurrentRound();
        $totalRounds = $dod->getTotalRounds();

        //get game state
        $state = $dod->getState();
    }
} catch (Exception $e) {
    //show any errors
    echo "Caught Exception: " . $e->getMessage() . ' | Line: ' . $e->getLine() . ' | File: ' . $e->getFile();
    //$msg[] = array("msg" => "game-not-found", "popup" => "dialog");

}

require_once(ROOT."/games/drink-or-dare/play/header.php");

?>

<div class="mdl-layout mdl-js-layout mdl-layout--fixed-header" id="game-content">
    <header class="mdl-layout__header mdl-layout__header--transparent">
        <div class="mdl-layout__header-row">
            <span class="mdl-layout-title" id="currentRound"><?php echo "Round " . $roundNum . "/" . $totalRounds; ?></span>
            <!-- Add spacer, to align navigation to the right -->
            <div class="mdl-layout-spacer"></div>
            <!-- Navigation -->
            <nav class="mdl-navigation">
                <h6 style="margin: 0 5px;"><?php echo $code; ?></h6>
                <?php
                if($isHost) {
                    ?>
                    <button id="settings" class="mdl-button mdl-js-button mdl-button--icon">
                        <i class="fa fa-cog fade"></i>
                    </button>
                    <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect" for="settings">
                    <?php
                    if(!$isHost) {
                        ?>
                        <!--<li class="mdl-menu__item" id="leave-game" onclick="window.location.href = '../../../lobby/leave.php';">Leave Game</li>-->
                        <?php
                    } else if ($isHost) {
                        ?>
                        <form action="../../../lobby/" method="post" id="delete-game-form">
                            <input type="hidden" name="delete-game" value="true"/>
                        </form>
                        <li class="mdl-menu__item" id="delete-game" style="color: #CE0000"onclick="if(confirm('Are you sure you want to delete the game?')){$('#delete-game-form').submit();}">Delete Game</li>
                        <?php
                    }
                }
                ?>
            </nav>
        </div>
    </header>

    <div class="mdl-layout__drawer leaderboard" style="background: none; border: none; box-shadow: none;">
        <div class="loadLeaderboard"></div>
    </div>

    <main class="mdl-layout__content">
        <?php
        if($isDisplay) {
            ?>
            <div class="mdl-cell mdl-cell--4-col dares center">
                <h1 style="color: #fff;"><?php echo ($state == 5 ? 'Game Over' : 'Leaderboard'); ?></h1>
                <div class="leaderboard">
                    <div class="loadLeaderboard"></div>
                    <?php
                    if($isHost && $state == 5) {
                        ?>
                        <a onclick="restartGame();" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-color--primary mdl-color-text--primary-contrast right restart-button">Restart Game</a>
                        <?php
                    }
                    ?>
                </div>
                <h4 style="color: #fff;<?php echo ($state == 1 ? : 'display:none'); ?>" id="game-stage-1">Creating dares...</h4>
                <div style="<?php echo ($state == 3 ? : 'display:none;'); ?>" id="game-stage-1-waiting"></div>

                <!-- Stage 2 -->
                <h4 style="color: #fff;<?php echo ($state == 2 ? : 'display:none'); ?>" id="game-stage-2">Picking dares...</h4>

                <!-- Stage 3 -->
                <div style="<?php echo ($state == 3 ? : 'display:none;'); ?>" id="game-stage-3">
                    <div id="activePlayer"></div>
                </div>
                <div style="<?php echo ($state == 3 ? : 'display:none;'); ?>" id="game-stage-4"></div>
                <div style="<?php echo ($state == 3 ? : 'display:none;'); ?>" id="game-stage-5"></div>
            </div>
            <?php
        } else {
            ?>
            <!-- Stage 1 -->
            <div class="mdl-card mdl-shadow--6dp center" <?php echo ($state == 1 && !$dod->getHasCurrentDare() ? : 'style="display:none"'); ?> id="game-stage-1">
                <div class="mdl-card__title">
                    <h2 class="mdl-card__title-text">What's Your Dare?</h2>
                </div>
                <div class="mdl-card__supporting-text">
                    <form action="" method="post">
                        <div class="mdl-textfield mdl-js-textfield">
                            <textarea class="mdl-textfield__input" type="text" rows= "6" id="dare-text"></textarea>
                            <label class="mdl-textfield__label" for="dare-text">Enter it here..</label>
                        </div>
                    </form>
                </div>
                <div class="mdl-card__actions" style="text-align: center; margin-top: -35px;">
                    <p>How many drinks is this dare worth?</p>
                    <label class="mdl-radio drinksWorth mdl-js-radio mdl-js-ripple-effect" for="drinksWorth-1">
                        <input type="radio" id="drinksWorth-1" class="mdl-radio__button" name="drinksWorth" value="1">
                        <span class="mdl-radio__label">1</span>
                    </label>
                    <label class="mdl-radio drinksWorth mdl-js-radio mdl-js-ripple-effect" for="drinksWorth-2">
                        <input type="radio" id="drinksWorth-2" class="mdl-radio__button" name="drinksWorth" value="2">
                        <span class="mdl-radio__label">2</span>
                    </label>
                    <label class="mdl-radio drinksWorth mdl-js-radio mdl-js-ripple-effect" for="drinksWorth-3">
                        <input type="radio" id="drinksWorth-3" class="mdl-radio__button" name="drinksWorth" value="3">
                        <span class="mdl-radio__label">3</span>
                    </label>
                    <label class="mdl-radio drinksWorth mdl-js-radio mdl-js-ripple-effect" for="drinksWorth-4">
                        <input type="radio" id="drinksWorth-4" class="mdl-radio__button" name="drinksWorth" value="4">
                        <span class="mdl-radio__label">4</span>
                    </label>
                    <label class="mdl-radio drinksWorth mdl-js-radio mdl-js-ripple-effect" for="drinksWorth-5">
                        <input type="radio" id="drinksWorth-5" class="mdl-radio__button" name="drinksWorth" value="5">
                        <span class="mdl-radio__label">5</span>
                    </label>
                    <button class="mdl-button mdl-js-button mdl-js-ripple-effect" onclick="setDare();" style="width: 100%; margin-top: 15px;">Done</button>
                </div>
            </div>
            <div class="mdl-card mdl-shadow--6dp center" <?php echo ($state == 1 && $dod->getHasCurrentDare() ? : 'style="display:none"'); ?> id="game-stage-1-waiting">
                <div class="mdl-card__supporting-text">
                    <p>Waiting for other players to enter dares..</p>
                </div>
            </div>

            <!-- Stage 2 -->
            <div class="mdl-cell mdl-cell--8-col dares center" id="game-stage-2" <?php echo ($state == 2 ? : 'style="display:none"'); ?>>
                <h4 style="color: #fff; margin: -50px 0 10px 0;">Pick a Dare!</h4>
                <?php
                $count = 0;
                foreach($game['users'] as $u) {
                    if(!$u['is_display']) {
                        $count++;
                        ?>
                        <div class="mdl-card mdl-shadow--6dp square paper dare pickCard" data-cardnum="<?php echo $count; ?>">
                        </div>
                        <?php
                    }
                }
                ?>
            </div>

            <!-- Stage 3 -->
            <div class="mdl-cell mdl-cell--3-col mdl-cell--6-col-tablet mdl-cell--8-col-phone center" style="<?php echo ($state == 3 ? : 'display:none;'); ?> min-width: 300px;" id="game-stage-3">

            <div <?php echo ($dod->getIsMyTurn() ? '' : 'style="display:none"'); ?> id="game-stage-3-player">
                <div class="mdl-card mdl-shadow--6dp square dare full-width paper showCard" id="myCard">
                    <?php
                    $dare = $dod->getDare(true, true);
                    if($dod->checkHasPeeked()) {
                        ?>
                        <h2 class="activeDrinksWorth"><?php echo $dare['drinks_worth']; ?></h2>
                        <div class="activeDrinksWorthPic">
                            <img src='/join/pictures/party/pint.png' />
                        </div>
                        <h5 class='dareText'><?php echo $dare['dare'] ?></h5>
                        <?php
                    } else if($dod->getIsMyTurn()) {
                        ?>
                        <h5 class='dareText' style='margin-top: 37%;'>It's Your Turn!<Br />Click me to reveal your dare!</h5>
                        <?php
                    } else {
                        $owner = $dod->getOwner(true, $dare['id']);
                        $ownerName = $owner['display_name'];
                        ?>
                        <h5 class="dareText" style='margin-top: 37%;'>Waiting for <?php echo $ownerName; ?>...</h5>
                        <?php
                    }
                    ?>
                </div>
                <div class="mdl-cell mdl-cell--12-col actions center" id="action-buttons">
                    <button id="only-skip" class="mdl-button mdl-js-button mdl-button--fab mdl-js-ripple-effect mdl-color--primary mdl-button--colored right" onclick="freePass();">
                        <i class="fa fa-fast-forward"></i>
                    </button>
                    <div class="mdl-tooltip mdl-tooltip--large" for="only-skip">
                        Use a Free Pass
                    </div>
<!--                    <button id="done-dare" class="mdl-button mdl-js-button mdl-button--fab mdl-js-ripple-effect mdl-color--green mdl-button--colored right" onclick="finishDare();">-->
<!--                        <i class="fa fa-check"></i>-->
<!--                    </button>-->
<!--                    <div class="mdl-tooltip mdl-tooltip--large" for="done-dare">-->
<!--                        I'm done the dare!-->
<!--                    </div>-->
                </div>
            </div>

            <div <?php echo (!$dod->getIsMyTurn() ? '' : 'style="display:none"'); ?> id="game-stage-3-viewer">
                <div class="mdl-card mdl-shadow--6dp square dare full-width paper showCard" id="activeDare">
                    <?php
                    $dare = $dod->getDare(true, true);
                    if($dod->checkHasPeeked(true)) {
                        ?>
                        <h2 class="activeDrinksWorth"><?php echo $dare['drinks_worth']; ?></h2>
                        <div class="activeDrinksWorthPic">
                            <img src='/join/pictures/party/pint.png' />
                        </div>
                        <h5 class='dareText'><?php echo $dare['dare'] ?></h5>
                        <?php
                    } else if($dod->getIsMyTurn()) {
                        ?>
                        <h5 class='dareText' style='margin-top: 37%;'>It's Your Turn!<Br />Click me to reveal your dare!</h5>
                        <?php
                    } else {
                        $owner = $dod->getOwner(true, $dare['id']);
                        $ownerName = $owner['display_name'];
                        ?>
                        <h5 class="dareText" style='margin-top: 37%;'>Waiting for <?php echo $ownerName; ?>...</h5>
                        <?php
                    }
                    ?>
                </div>
                <div class="mdl-cell mdl-cell--12-col actions center">
                    <button id="drink" class="mdl-button mdl-js-button mdl-button--fab mdl-js-ripple-effect mdl-color--red mdl-button--colored left" onclick="castVote(1);">
                        <i class="fa fa-remove"></i>
                    </button>
                    <div class="mdl-tooltip mdl-tooltip--large" for="drink">
                        Dare execution not worthy!
                    </div>
                    <button id="free-skip" class="mdl-button mdl-js-button mdl-button--fab mdl-js-ripple-effect mdl-color--primary mdl-button--colored middle" onclick="castVote(2);">
                        <i class="fa fa-fast-forward"></i>
                    </button>
                    <div class="mdl-tooltip mdl-tooltip--large" for="free-skip">
                        That dare is unreasonable.
                    </div>
                    <button id="give-drink" class="mdl-button mdl-js-button mdl-button--fab mdl-js-ripple-effect mdl-color--green mdl-button--colored right" onclick="castVote(3);">
                        <i class="fa fa-check"></i>
                    </button>
                    <div class="mdl-tooltip mdl-tooltip--large" for="give-drink">
                        Well done Jackson!
                    </div>
                </div>
            </div>

                <div class="votes" id="votes">
                    <h3><span id="num-votes">0</span><span>/<?php echo $dod->getNumPlayers() - 1; ?></span> Votes</h3>
                </div>
            </div>

            <!-- Stage 4 -->
            <div class="mdl-card mdl-shadow--6dp center" <?php echo ($state == 4 ? : 'style="display:none"'); ?> id="game-stage-4">
                <div class="mdl-card__supporting-text">
                </div>
            </div>

            <!-- Stage 5 -->
            <div class="mdl-cell mdl-cell--4-col dares center" id="game-stage-5" <?php echo ($state == 5 ? : 'style="display:none"'); ?>>
                <h1 style="color: #fff;">Game Over</h1>
                <div class="leaderboard">
                    <div class="loadLeaderboard"></div>
                    <?php
                    if($isHost) {
                        ?>
                        <a onclick="restartGame();" class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-color--primary mdl-color-text--primary-contrast right restart-button">Restart Game</a>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        ?>
    </main>
</div>

<?php
require_once(ROOT."/games/drink-or-dare/play/footer.php");
?>

