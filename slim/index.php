<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);


date_default_timezone_set('Australia/Brisbane');
define('SAVINGS_ACCOUNT', 'savings');
define('SPENDING_ACCOUNT', 'spending');
define('CHANGE_ACCOUNT', 'change');
define('DAY_IN_SECONDS', 86400);

// Autoloads all the stuff we have brought in
// using composer (thats quite nice)
require 'vendor/autoload.php';
require 'rb.phar';
require 'vendor/netbank/netbank.php';

// Register slim auto-loader
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
	'templates.path' => './templates'
));

// Load all the Slim stuff
require 'models/models.php';
require 'middleware/middleware.php';

// Routes
require 'routes/session.php';
require 'routes/member.php';
require 'routes/admin.php';

// DB
require 'db/db.php';

// Add session middleware
$app->add(new \Slim\Middleware\SessionCookie(
	array(
		'secret' => 'myappsecret',
		'expires' => '7 days',
	))
);
// Add camelcase middleware
$app->add(new \CamelCaseMiddleware());

$app->get('/users/:userId/bank/accounts/import', function($userId) use ($app) {
	$user = R::load('user', $userId);

	$passwords = json_decode(file_get_contents("bank/bank.pass"));
	$netbank = new NetBank($passwords->netbank->username, $passwords->netbank->password);

	$bankAccounts = $netbank->retrieveAccounts();
	// echo json_encode($bankAccounts);
	foreach($bankAccounts as $bankAccount) {
		$account = R::dispense('account');
		$account->user = $user;
		$account->bankId = $bankAccount->Id;
		$account->name = $bankAccount->AccountName;
		$account->number = $bankAccount->AccountNumber;
		$account->availableFunds = $bankAccount->AvailableFunds;
		$account->balance = $bankAccount->Balance;
		R::store($account);
	}
});
$app->get('/test/bankaccounts', function() use ($app) {
	$passwords = json_decode(file_get_contents("bank/.passwords"));
	$netbank = new NetBank($passwords->netbank->username, $passwords->netbank->password);
	$bankAccounts = $netbank->retrieveAccounts();
	echo json_encode($bankAccounts);
});
$app->get('/test/transactions/:accountId', function($accountId) use ($app) {
	$passwords = json_decode(file_get_contents("bank/.passwords"));
	$netbank = new NetBank($passwords->netbank->username, $passwords->netbank->password);
	
	$transactions = $netbank->getAccountTransactions($accountId);
	echo json_encode($transactions);
});
$app->get('/test/newuser', function() use ($app) {
	$user = R::load('user', 1);

	$sampleUserData = array(
		'firstName' 	=> 'Craig',
		'lastName'		=> 'McNamara',
		'income'		=> 1969.61,
		'incomePeriod'	=> 14,
		'timezone'		=> 'Australia/Brisbane'
	);

	// $userData = json_decode($app->request->getBody());
	$userData = $sampleUserData;

	$user = R::dispense('user');
	$user->import($userData);
	$user->processed = mktime(0,0,0);
	R::store($user);

	// Setup the change
	$transaction = R::dispense('transaction');
    $transaction->account = CHANGE_ACCOUNT;
    $transaction->description = "Initial setup.";
    $transaction->amount = 0;
    $transaction->time = mktime(0,0,0);
    $transaction->user = $user;
    R::store($transaction);

	echo json_encode($user->export(), JSON_NUMERIC_CHECK);
});

/**
 * Processes a goal
 *
 * Takes care of moving money around, transactions etc for a given date
 * @param  [Goal] 	$goal The goal to be processed
 * @param  [Epoch]  $date Epoch time of the date to the processed (12am of the date)
 */
// function processGoal($goal, $date) {

// 	// Is it due? (round it to nearest day)
// 	$daysLeft = floor(($goal->due - $date) / DAY_IN_SECONDS);
// 	$isDue = $daysLeft == 0;
	
// 	// Is 'today' the due date?
// 	if($isDue) {
// 		_processDueGoal($goal);
// 	} 

// 	// Transfer income to savings for goal
// 	// Calculate amount to transfer
// 	// Reload the goal (since all its amounts have changed) from the processing
// 	$goal = R::load('goal', $goal->id);
// 	$amountRemaining = $goal->amount - $goal->savings;
// 	$saveAmount = $goal->daily;

// 	if($amountRemaining > 0) {	
// 		// Transfer money in
// 		$transaction = R::dispense('transaction');
// 		$transaction->goal = $goal;
//     	$transaction->account = SAVINGS_ACCOUNT;
//     	$transaction->amount = $saveAmount;
//     	$transaction->description = "Saving for goal.";
//     	$transaction->time = $date;
//     	R::store($transaction);
// 	}

// 	// Reload the goal after the transaction
// 	// and set its processed date
// 	$goal = R::load('goal', $goal->id);
// 	$goal->processed = $date;
// 	R::store($goal);

// 	return $saveAmount;
// }
/**
 * Processes a due goal.
 *
 * Does all the transaction shuffling for when a goal is due, and it repeats etc
 * 
 * @param  [Goal] $goal The goal that is due
 * @return [type]       [description]
 */


$app->get('/users', function() {
	$users = R::findAll('user');
	echo json_encode(R::exportAll($users), JSON_NUMERIC_CHECK);
});




// // Get goals for a user
// $app->get('/users/:id/goals', function($id) {
// 	$user = R::load('user', $id);
// 	$goals = $user->ownGoalList;
// 	echo json_encode(R::exportAll($goals), JSON_NUMERIC_CHECK);
// });

// $app->post('/users/:userId/goals', function($userId) use ($app) {
// 	// Get the post data
// 	$goalData = json_decode($app->request->getBody());

//     //Create Goal
//     $goal = R::dispense('goal');
//     $goal->import($goalData);
//     $goal->created = time();
//     $goal->modified = time();
//     $user = R::load('user', $userId);
//     $goal->user = $user;
//     R::store($goal);

//     // Have we processed up to today?
//     // if($user->processed == mktime(0,0,0)) {
//     	// We have already processed up to today
//     	// So we are going to have to manually add in the allocation transaction
//     	// for this goal
    	
// 		// Work out how much we need to save today
// 	    $daysLeft = floor(($goal->due - $user->processed) / 60 / 60 / 24);

// 	    $transaction = R::dispense('transaction');
// 	    $transaction->account = SAVINGS_ACCOUNT;
// 	    $transaction->description = "Initial saving.";
// 	    $transaction->time = $user->processed; 
// 	    // @todo work out rounding
// 	    $transaction->amount = $goal->amount / $daysLeft;
// 	    $transaction->goal = $goal;
// 	    $transaction->userId = $userId;
// 	    R::store($transaction);
//     // }
	
// 	$goal = R::load('goal', $goal->id);
// 	$goal->processed = $user->processed;
// 	R::store($goal);
//     echo json_encode($goal->export(), JSON_NUMERIC_CHECK);
// });



// $app->delete('/users/:userId/goals/:goalId', function($userId, $goalId) {
// 	$goal = R::load('goal', $goalId);
	
// 	// Transfer available balance to loose change
// 	$looseChangeAccount  = R::findOne( 'account', " type = 'change' AND user_id = {$userId}");
// 	// Transfer available to loose change
// 	$transaction = R::dispense('transaction');
//     $transaction->amount = $goal->availableAmount + $goal->savedAmount;
//     $transaction->description = "Deleting goal. Transferring allocated funds to loose change.";
//     $transaction->account = 'change';
//     R::store($transaction);
//     $looseChangeAccount->ownTransactionList[] = $transaction;
//     R::store($looseChangeAccount);

//     // Delete a goals transactions
// 	$goal->xownTransactionList = array();
// 	R::store($goal);
// 	R::trash($goal); 
// });
// $app->put('/users/:userId/goals/:goalId', function($userId, $goalId) use ($app) {
// 	// Get the put data
// 	$goalData = json_decode($app->request->getBody());

// 	$goal = R::load('goal', $goalId);
// 	unset($goalData->ownTransaction);
// 	$goal->import($goalData);
// 	$goal->nextDue = strtotime($goal->firstDue);
// 	// Save the goal
//     R::store($goal);

//     // Delete the current saving transaction for today
//     // Because we are going to replace it with the one towards our new goal
//     $transactions = $goal->withCondition(' type = ? AND created = ? ', ['saving', mktime(0,0,0)] )->ownTransactionList;
//     // R::trashAll($transactions);
//     foreach($transactions as $transaction) {
//     	unset( $goal->xownTransactionList[$transaction->id] );
//     }
//     // // Save the goal
//     // R::store($goal);

//     // Work out how much we need to save today
//     $daysLeft = floor(($goal->nextDue - mktime(0, 0, 0)) / 60 / 60 / 24);

//     $transaction = R::dispense('transaction');
//     $transaction->amount = $goal->goalAmount / $daysLeft;
//     $transaction->description = "Initial saving.";
//     $transaction->account = 'saving';
//     $transaction->goal = $goal;
//     R::store($transaction);

//     $goal->ownTransactionList[] = $transaction;
//     R::store($goal);

//     echo json_encode($goal->export());
// });




// function processGoalsForUser($goals, $user) {
	
// 	// Get out the loose change account for use later
// 	$looseChangeAccount  = R::findOne( 'account', " type = 'change' AND user_id = {$user->id}");

// 	// Calculate the days since it was processed
// 	$daysSinceLastProcessed = floor(abs($user->lastProcessed - mktime(0, 0, 0))/60/60/24);

// 	$realTodayTime = mktime(0, 0, 0);

// 	if($daysSinceLastProcessed > 0) {
// 		// We need to do some processing
		
// 		for($i = 0; $i < $daysSinceLastProcessed; $i++) {
// 			// We need to simulate going through all those days we missed
// 			// So we need to work out what 'day' we are looking at
// 			$todayTime = $realTodayTime - (($daysSinceLastProcessed - ($i + 1)) * 60 * 60 * 24);

// 			// As we go through each goal, we will count up how much money we have allocated
// 			$allocatedAmount = 0;

// 			foreach($goals as $goal) {
// 				$daysLeft = floor(($goal->nextDue - $todayTime) / 60 / 60 / 24);
				
// 				// Is 'today' the due date?
// 				if($daysLeft == 0) {

// 					// @todd special case where no money has been saved yet (due today with no savings)
// 					if($goal->savedAmount == 0) {
// 						// @todo add transactions here
// 						// No money saved but its due!
						
// 						// Take it out of loose change
// 						$transaction = R::dispense('transaction');
// 					    $transaction->amount = 0 - $goal->goalAmount;
// 					    $transaction->description = "Withdrawing from loose change to pay goal.";
// 					    $transaction->account = 'change';
// 					    R::store($transaction);
// 					    $looseChangeAccount->ownTransactionList[] = $transaction;

// 					    // Put it in available
// 						$transaction = R::dispense('transaction');
// 					    $transaction->amount = $goal->goalAmount;
// 					    $transaction->description = "Making change available to complete goal set for today.";
// 					    $transaction->account = 'available';
// 					    R::store($transaction);
// 					    $goal->ownTransactionList[] = $transaction;
// 					} else {
// 						// Take the current available and put it to loose change
// 						// Take it out of available
// 					    $transaction = R::dispense('transaction');
// 					    $transaction->amount = 0 - $goal->availableAmount;
// 					    $transaction->description = "Goal due. Sending any unused available amount to loose change";
// 					    $transaction->account = 'available';
// 					    R::store($transaction);
// 					    $goal->ownTransactionList[] = $transaction;

// 						// Transfer available to loose change
// 						$transaction = R::dispense('transaction');
// 					    $transaction->amount = $goal->availableAmount;
// 					    $transaction->description = "Goal due. Received surplus available amount from goal.";
// 					    $transaction->account = 'change';
// 					    R::store($transaction);
// 					    $looseChangeAccount->ownTransactionList[] = $transaction;

// 					    // Save the saving, and put it in available
// 						// Take it out of saving
// 						$transaction = R::dispense('transaction');
// 					    $transaction->amount = 0 - $goal->savedAmount;
// 					    $transaction->description = "Goal due. Transferring savings amoun to available";
// 					    $transaction->account = 'saving';
// 					    R::store($transaction);
// 					    $goal->ownTransactionList[] = $transaction;

// 					    // Put it in available
// 					    $transaction = R::dispense('transaction');
// 					    $transaction->amount = $goal->savedAmount;
// 					    $transaction->description = "Goal due. Receved savings amount to available.";
// 					    $transaction->account = 'available';
// 					    R::store($transaction);
// 					    $goal->ownTransactionList[] = $transaction;
// 					}

// 					// Calcualte the next due day
// 					$fauxTodayString = date("m/d/y", $todayTime);
// 					switch($goal->repeat) {
// 						case 'none':
// 							$goal->nextDue = null;
// 							break;
// 						case 'day':
// 							$goal->nextDue = strtotime($fauxTodayString . " + 1 day");
// 							break;
// 						case 'weekday':
// 							// @todo work out weekday maths
// 							$goal->nextDue = strtotime($fauxTodayString . " + 1 weekday");
// 							break;
// 						case 'week':
// 							$goal->nextDue = strtotime($fauxTodayString . " + 1 week");
// 							break;
// 						case 'fortnight':
// 							$goal->nextDue = strtotime($fauxTodayString . " + 2 weeks");
// 							break;
// 						case 'month':
// 							$goal->nextDue = strtotime($fauxTodayString . " + 1 month");
// 							break;
// 						case 'quarter':
// 							$goal->nextDue = strtotime($fauxTodayString . " + 3 months");
// 							break;
// 						case 'year':
// 							$goal->nextDue = strtotime($fauxTodayString . " + 1 year");
// 							break;
// 					}
// 				} 

// 				// Transfer income to savings for goal
// 				// Calculate amount to transfer
// 				$amountRemaining = $goal->goalAmount - $goal->savedAmount;

// 				if($amountRemaining > 0) {
// 					// echo "next due " . $goal->nextDue . "<br/>";
// 					$daysLeft = floor(($goal->nextDue - $todayTime) / 60 / 60 / 24);
					
// 					// Work out how much to transfer to savings
// 					$saveAmount = $amountRemaining / $daysLeft;

// 					$transaction = R::dispense('transaction');
// 				    $transaction->amount = $saveAmount;
// 				    $transaction->description = "Save amount";
// 				    $transaction->account = 'saving';
// 				    R::store($transaction);
// 				    $goal->ownTransactionList[] = $transaction;

// 					// @todo add transaction history
// 					$allocatedAmount += $saveAmount;
// 				}
				
// 				// Save the goal
// 				R::store($goal);
// 				R::store($looseChangeAccount);
// 			}
// 			// end foreach goals

// 			// Update the last processed day
// 			$user->lastProcessed = $todayTime;

// 			// Transfer unallocated amount to loose change
// 			// @todo add transaction history
// 			$unallocatedAmount = ($user->income / $user->incomePeriod) - $allocatedAmount;

// 			// Transfer unallocated daily funds
// 			// to loose change
// 			$transaction = R::dispense('transaction');
// 		    $transaction->amount = $goal->availableAmount;
// 		    $transaction->description = "Send unallocated daily funds";
// 		    $transaction->account = 'change';
// 		    R::store($transaction);
// 		    $looseChangeAccount->ownTransactionList[] = $transaction;

// 			R::store($looseChangeAccount);
// 		}
// 		// end foreach days
		
// 		// Save the loose change
// 		R::store($looseChangeAccount);

// 		// Save the user
// 		$user->lastProcessed = $realTodayTime;
// 		R::store($user);	
// 	}
// }


// $app->get('/goals', function () {
// 	// Get the user

// 	// Get the time of 12:00am today
// 	$realTodayTime = mktime(0, 0, 0);

// 	$goals = R::findAll( 'goal' );

// 	// $user = R::load('user', 3);	
// 	$user->lastProcessed = mktime(-48, 0, 0);
	

// 	echo "today is: " . date('d/m/Y', $realTodayTime) . "<br/>";
// 	// Calculate the days since it was processed
// 	$daysSinceLastProcessed = floor(abs($user->lastProcessed - $realTodayTime)/60/60/24);
// 	echo "last processed: " . date('d/m/Y', $user->lastProcessed) . "<br/>";
// 	echo "days since last processed: " . $daysSinceLastProcessed . "<br/>";

// 	if($daysSinceLastProcessed > 0) {
// 		// We need to do some processing
		
// 		for($i = 0; $i < $daysSinceLastProcessed; $i++) {
// 			// We need to simulate going through all those days we missed
// 			// So we need to work out what 'day' we are looking at
// 			$todayTime = $realTodayTime - (($daysSinceLastProcessed - ($i + 1)) * 60 * 60 * 24);

// 			echo "<br/><b>Day is: " . date('d/m/Y', $todayTime) . "</b><br/>";
// 			// As we go through each goal, we will count up how much money we have allocated
// 			$allocatedAmount = 0;

// 			foreach($goals as $goal) {
// 				echo "Goal: " . $goal->for . "<br/>";
// 				$daysLeft = floor(($goal->nextDue - $todayTime) / 60 / 60 / 24);
// 				echo "Next due: " . date('d/m/Y', $goal->nextDue) . ' - Days left: ' . $daysLeft . "<br/>";

// 				// Is 'today' the due date?
// 				if($daysLeft == 0) {
// 					echo "Goal is due today!<br/>";

// 					echo "- Goal balance<br/>";
// 					echo "<pre>";
// 					print_r($goal->export());
// 					echo "</pre>";

// 					// @todd special case where no money has been saved yet (due today with no savings)
// 					if($goal->savedAmount == 0) {
// 						echo "No savings!<br/>";
// 						echo "- Loose Change Balance<br/>";
// 						echo "<pre>";
// 						print_r($user->looseChange);
// 						echo "</pre>";
// 						// @todo add transactions here
// 						// No money saved but its due!
// 						// Take it out of loose change
// 						$transaction = $R::dispense('transaction');
// 						$

// 						$user->looseChange -= $goal->goalAmount;
// 						$goal->availableAmount += $goal->goalAmount;

// 						echo "- Withdrawing from Loose Change. Balance is<br/>";
// 						echo "<pre>";
// 						print_r($user->looseChange);
// 						echo "</pre>";
// 					} else {
// 						// @todo add transaction history
// 						// Transfer available to loose change
// 						$user->looseChange += $goal->availableAmount;
// 						$goal->availableAmount = 0;

// 						// Transfer savings to available
// 						$goal->availableAmount = $goal->savedAmount;
// 						$goal->savedAmount = 0;

// 						echo "- Transferring savings to available<br/>";
// 						echo "<pre>";
// 						print_r($goal->export());
// 						echo "</pre>";
// 					}

// 					// @todo: Calculate next due date
// 					// echo date("jS F, Y H:i:s", strtotime("today + 2 weeks"));
// 					$fauxTodayString = date("m/d/y", $todayTime);

// 					switch($goal->repeat) {
// 						case 'none':
// 							$goal->nextDue = null;
// 							break;
// 						case 'day':
// 							$goal->nextDue = strtotime($fauxTodayString . " + 1 day");
// 							break;
// 						case 'weekday':
// 							// @todo work out weekday maths
// 							$goal->nextDue = strtotime($fauxTodayString . " + 1 weekday");
// 							break;
// 						case 'week':
// 							$goal->nextDue = strtotime($fauxTodayString . " + 1 week");
// 							break;
// 						case 'fortnight':
// 							$goal->nextDue = strtotime($fauxTodayString . " + 2 weeks");
// 							break;
// 						case 'month':
// 							$goal->nextDue = strtotime($fauxTodayString . " + 1 month");
// 							break;
// 						case 'quarter':
// 							$goal->nextDue = strtotime($fauxTodayString . " + 3 months");
// 							break;
// 					}

// 					echo "new date for when goal is due: " . date('d/m/Y', $goal->nextDue) . "<br/>";
// 					echo "<pre>";
// 					print_r($goal->export());
// 					echo "</pre>";
// 				} 

// 				// Transfer income to savings for goal
// 				// Calculate amount to transfer
// 				$amountRemaining = $goal->goalAmount - $goal->savedAmount;
// 				echo "Amount remaining to be saved: $" . $amountRemaining . "<br/>";

// 				if($amountRemaining > 0) {
// 					// echo "next due " . $goal->nextDue . "<br/>";
// 					$daysLeft = floor(($goal->nextDue - $todayTime) / 60 / 60 / 24);
					
// 					// Work out how much to transfer to savings
// 					$saveAmount = $amountRemaining / $daysLeft;

// 					$goal->savedAmount += $saveAmount;
// 					// @todo add transaction history
// 					$allocatedAmount += $saveAmount;

// 					echo "Saved a little $" . $saveAmount .  "<br/>";
// 					echo "<pre>";
// 					// print_r(R::export($goal));
// 					echo "</pre>";

// 				}
				
// 				// Save the goal
// 				// R::store($goal);
// 				// R::store($user);
// 			}

// 			// Update the last processed day
// 			$user->lastProcessed = $todayTime;

// 			// Transfer unallocated amount to loose change
// 			// @todo add transaction history
// 			$unallocatedAmount = ($user->income / $user->incomePeriod) - $allocatedAmount;
// 			$user->looseChange += $unallocatedAmount;
			
// 			// R::store($user);
// 		}

// 		$user->lastProcessed = $realTodayTime;
// 		// R::store($user);	
// 	} 

// 	echo json_encode(R::exportAll($goals), JSON_NUMERIC_CHECK);
// });




$app->run();

