<?php

// Restricted to logged in current user
$app->group('/member', $authenticate($app), function () use ($app) {

	/**
	 * ============
	 * Current logged in user
	 * ============
	 */
	$app->get('/user', function() {
		$user = R::load('user', $_SESSION['userId']);
		echo json_encode($user->export(), JSON_NUMERIC_CHECK);
	});

	/**
	 * ============
	 * Goals
	 * ============
	 */
	// GET
	$app->get('/goals', function() {
		$user = R::load('user', $_SESSION['userId']);
		$goals = $user->ownGoalList;
		echo json_encode(R::exportAll($goals), JSON_NUMERIC_CHECK);
	});
	// POST
	$app->post('/goals', function() use ($app) {
		// Get the post data
		$goalData = json_decode($app->request->getBody());

	    //Create Goal
	    $goal = R::dispense('goal');
	    $goal->import($goalData);
	    $user = R::load('user', $_SESSION['userId']);
	    $goal->user = $user;
	    R::store($goal);

	    _processGoal($goal, $user->processed);
	    $goal = R::load('goal', $goal->id);
	    echo json_encode($goal->export(), JSON_NUMERIC_CHECK);
	});

	/**
	 * ============
	 * Goal
	 * ============
	 */
	// GET
	$app->get('/goals/:id', function ($id) {
		$goal = R::load('goal', $id);
    	echo json_encode($goal->export(), JSON_NUMERIC_CHECK);
	});
	// DELETE
	$app->delete('/goals/:id', function($id) {
		$goal = R::load('goal', $id);
		$user = R::load('user', $_SESSION['userId']);

		// Delete the saving transaction from today
		_deleteSavingsForGoalForDate($goal, $user->processed);

		// Reload the goal
		$goal = R::load('goal', $id);

		$spendingAccountBalance = $goal->{SPENDING_ACCOUNT};
		$savingsAccountBalance = $goal->{SAVINGS_ACCOUNT};

		// Transfer any remaining money to change
		$transaction = R::dispense('transaction');
		$transaction->user = $user;
		$transaction->account = CHANGE_ACCOUNT;
	    $transaction->amount = $spendingAccountBalance + $savingsAccountBalance;
	    $transaction->time = $user->processed;
	    $transaction->description = $goal->name . ' closing balance.';
	    R::store($transaction);

		$goal->xownTransactionList = array();
		R::store($goal);
		R::trash($goal); 
	});
	// PUT
	$app->put('/goals/:goalId', function($goalId) use ($app) {
		// Update the goal
		$goal = R::load('goal', $goalId);
		$goalData = json_decode($app->request->getBody());
		unset($goalData->ownTransaction);
		unset($goalData->modifed);
		unset($goalData->created);
		unset($goalData->daily);
		unset($goalData->userId);
		unset($goalData->savings);
		unset($goalData->spending);
		unset($goalData->processed);
		$goal->import($goalData);
		R::store($goal);
		
		// Process the goal
		$user = R::load('user', $goal->userId);
		_processGoal($goal, $user->processed);
		$goal = R::load('goal', $goal->id);
		echo json_encode($goal->export(), JSON_NUMERIC_CHECK);
	});

	// Targetted Action
	/**
	 * Cancels the saving for a goal, transfers balance to change
	 */
	$app->post('/goals/:id/cancel', function($id) use ($app) {
		$goal = R::load('goal', $id);
		$user = R::load('user', $_SESSION['userId']);

		// Delete the saving transaction from today
		_deleteSavingsForGoalForDate($goal, $user->processed);

		// Reload the goal
		$goal = R::load('goal', $id);

		$savingsAccountBalance = $goal->{SAVINGS_ACCOUNT};

		$transaction = R::dispense('transaction');
		$transaction->goal = $goal;
		$transaction->account = SAVINGS_ACCOUNT;
		$transaction->amount = 0 - $savingsAccountBalance;
		$transaction->description = 'Cancelled goal.';
		$transaction->time = $user->processed;
		R::store($transaction);	

		// Transfer any remaining money to change
		$transaction = R::dispense('transaction');
		$transaction->user = $user;
		$transaction->account = CHANGE_ACCOUNT;
	    $transaction->amount = $savingsAccountBalance;
	    $transaction->time = $user->processed;
	    $transaction->description = 'Cancelled ' . $goal->name;
	    R::store($transaction);

	    // Set the goal's due date to be null
	    // Reload the goal
		$goal = R::load('goal', $id);
		$goal->due = null;
		$goal->daily = 0;
		R::store($goal);

		$goal = R::load('goal', $id);
		echo json_encode($goal->export(), JSON_NUMERIC_CHECK);
	});
	/**
	 * Transfers the balance of the spending for a goal to change
	 */
	$app->post('/goals/:id/empty', function($id) use ($app) {
		$goal = R::load('goal', $id);
		$user = R::load('user', $_SESSION['userId']);

		$spendingAccountBalance = $goal->{SPENDING_ACCOUNT};

		$transaction = R::dispense('transaction');
		$transaction->goal = $goal;
		$transaction->account = SPENDING_ACCOUNT;
		$transaction->amount = 0 - $spendingAccountBalance;
		$transaction->description = 'Emptied';
		$transaction->time = $user->processed;
		R::store($transaction);	
	
		// Transfer any remaining money to change
		$transaction = R::dispense('transaction');
		$transaction->user = $user;
		$transaction->account = CHANGE_ACCOUNT;
	    $transaction->amount = $spendingAccountBalance;
	    $transaction->time = $user->processed;
	    $transaction->description = 'Income from emptied goal ' . $goal->name;
	    R::store($transaction);

		$goal = R::load('goal', $id);
		echo json_encode($goal->export(), JSON_NUMERIC_CHECK);
	});

	// Relationships
	// Get transactions for a goal
	$app->get('/goals/:id/transactions', function($id) {
		$goal = R::load('goal', $id);
		$transactions = $goal->ownTransactionList;
		echo json_encode(R::exportAll($transactions), JSON_NUMERIC_CHECK);
	});

		// Get change transactions for a user
	$app->get('/transactions/change', function() {

		$transactions = R::findAll('transaction',  ' user_id = :user_id AND account = :account ', 
        	array(':user_id' => $_SESSION['userId'], ':account' => CHANGE_ACCOUNT)
    	);
		echo json_encode(R::exportAll($transactions), JSON_NUMERIC_CHECK);
	});

	/**
	 * ============
	 * Transactions
	 * ============
	 */
	// GET
	$app->get('/transactions/sync', function() {
		
		$user = R::load('user', $_SESSION['userId']);
		$user->synced = time();
		R::store($user);

		// Get all transactions that do not have a goal assigned (or a user)
		$transactions = R::findAll('transaction',  ' user_id = :user_id AND goal_id IS NULL AND account = :type ORDER BY time ASC', 
	        array(':user_id' => $user->id, ':type' => SPENDING_ACCOUNT)
	    );

		// No unassigned transactions in our database, go to the bank
	    if(!count($transactions)) {
	    	// Connect to netbank

	    	$passwords = json_decode(file_get_contents("bank/bank.pass"));
			$netbank = new NetBank($passwords->netbank->username, $passwords->netbank->password);
			
			// Get the accounts we have stored
			$accounts = $user->ownAccountList;

	    	foreach($accounts as $account) {
				
				$bankTransactionsData = array_reverse($netbank->getAccountTransactions($account->bankId));
		    	// $bankTransactionsData = json_decode(file_get_contents("bank/transactions.json"));

				foreach($bankTransactionsData as $bankTransactionData) {

					$time = _bankDateToTime($bankTransactionData->EffectiveDate);

					if($time < $user->processed) {
						continue;
					}
				
					// Get out the values
					$amountString = substr($bankTransactionData->Amount, 1, strlen($bankTransactionData->Amount) - 3);
					$amountString = str_replace(",", "", $amountString); 
					$amount = floatval($amountString);
					$direction = substr($bankTransactionData->Amount, strlen($bankTransactionData->Amount) - 2, strlen($bankTransactionData->Amount));

					if($direction == 'DR') {
						$amount = 0 - $amount;
					}
					
					$description = $bankTransactionData->Description;

					// check its not a 'redo' pending
					if (strpos($description, 'Value Date') !== false) {
					    continue;
					}
				
					// Do we already have it stored?
					$existingTransactions = R::findOne('transaction', 'amount = :amount AND time = :time AND description = :description',
			        	array(':amount' => $amount, ':description' => $description, ':time' => $time)
			    	);

			    	if(!$existingTransactions) {
			    		// Not stored, store it
			    		$transaction = R::dispense('transaction');
				    	$transaction->account = SPENDING_ACCOUNT;
				    	$transaction->amount = $amount;
				    	$transaction->description = $description;
				    	$transaction->time = $time;
				    	$transaction->user = $user;
				    	$transaction->goalId = NULL;
				    	R::store($transaction);
			    	}
				}
	    	}

			// Get all transactions that do not have a goal assigned (or a user)
			$transactions = R::findAll('transaction',  ' user_id = :user_id AND goal_id IS NULL AND account = :type ORDER BY time ASC', 
		        array(':user_id' => $user->id, ':type' => SPENDING_ACCOUNT)
		    );
	    }

	    // Now do we have some transactions (after possibly going to the bank?)
		if(count($transactions)) {
			$transactions = array_values($transactions);
			$nextTransaction = $transactions[0];
			$processUntil = $nextTransaction->time;
			_processUserUntilDate($_SESSION['userId'], $processUntil);
		} else {
			// No transactions anywhere (none at the bank, and none we have stored)
			// So we can just sync up till today
			_processUserUntilDate($_SESSION['userId'], mktime(0,0,0));
		}
		echo json_encode(R::exportAll($transactions), JSON_NUMERIC_CHECK);
	});

	/**
	 * ============
	 * Transaction
	 * ============
	 */
	// GET
	$app->get('/transactions/:id', function($id) {
		$transaction = R::load('transaction', $id);
		echo json_encode($transaction->export(), JSON_NUMERIC_CHECK);
	});
	// PUT
	$app->put('/transactions/:id', function($id) use ($app) {
		$transaction = R::load('transaction', $id);
		$transactionData = json_decode($app->request->getBody());
		$transaction->import($transactionData);
		R::store($transaction);

		echo json_encode($transaction->export(), JSON_NUMERIC_CHECK);
	});
});

function _bankDateToTime($bankDate) {
	$parts = explode("/", $bankDate);
	$australianTime = $parts[0] . "-" . $parts[1] . "-20" . $parts[2];
	return strtotime($australianTime);
}


function _processUserUntilDate($userId, $processUntil) {

	$user = R::load('user', $userId);

	// Lets work out what dates we need to process for
	$dayCount = floor(($processUntil - $user->processed) / DAY_IN_SECONDS);
	$dates = array();
	for($i = 0; $i < $dayCount; $i++) {
		$date = $user->processed + (($i + 1) * DAY_IN_SECONDS);
		$dates[] = $date;
	}

	foreach($dates as $date) {
		// As we go through each goal, we will count up how much money we have allocated
		$allocatedAmount = 0;

		foreach($user->ownGoalList as $goal) {
			$allocatedAmount += $goal->daily;
		}

		// Transfer unallocated amount to loose change
		// @todo add transaction history
		$unallocatedAmount = ($user->income / $user->incomePeriod) - $allocatedAmount;

		// Transfer unallocated daily funds
		// to loose change
		$transaction = R::dispense('transaction');
		$transaction->user = $user;
		$transaction->account = CHANGE_ACCOUNT;
	    $transaction->amount = $unallocatedAmount;
	    $transaction->time = $date;
	    $transaction->description = "Unused income from yesterday.";
	    R::store($transaction);


	    // Now process the goal for the next day
		foreach($user->ownGoalList as $goal) {
			_processGoal($goal, $date);
		}

	    // Update the last processed day
		// We need to load it again because 'change' has been updated
	    $user = R::load('user', $userId);
		$user->processed = $date;
		R::store($user);
	}
}

/**
 * Finds any savings transactions for a goal for a date and deletes them.
 *
 * The scenario would be, you are going to change today's allocation, so you need
 * to remove the current allocation. Another scenario would be when deleting the goal,
 * you want to free up today's allocation.
 * 
 * @param  [type] $goal [description]
 * @param  [type] $date [description]
 * @return [type]       [description]
 */
function _deleteSavingsForGoalForDate($goal, $date) {
	$transactions = R::findAll('transaction',  ' goal_id = :goal_id AND account = :type AND time = :processedUntil AND amount >= 0 ', 
        array(':goal_id' => $goal->id, ':type' => SAVINGS_ACCOUNT, ':processedUntil' => $date)
    );
    if(count($transactions)) {
    	R::trashAll($transactions);	
    }
}

function _processGoal($goal, $date) {
	$goal = R::load('goal', $goal->id);
	$alreadyProcessed = $goal->processed == $date;

	// Has the goal been processed for this day already?
	if($alreadyProcessed) {
		// We have already processed it
		// Delete any saving transactions we have for 'today'
		_deleteSavingsForGoalForDate($goal, $date);

	    // Roll it back to 'yesterday'
	    $goal = R::load('goal', $goal->id);
	    $goal->processed = $date - DAY_IN_SECONDS;
	    R::store($goal);
	    
	    $goal = R::load('goal', $goal->id);
	} else {

		// If it hasn't been processed for today, then we couldnt have dealt
		// with it being due, so we can do that now
		
		// Is it due? (round it to nearest day)
		$daysLeft = floor(($goal->due - $date) / DAY_IN_SECONDS);
		$isDue = $daysLeft == 0;

		if($isDue) {
			// Is 'today' the due date?
			_processDueGoal($goal);	
		}
	}

	// Transfer income to savings for goal
	// Calculate amount to transfer
	// Reload the goal (since all its amounts have changed) from the processing
	$goal = R::load('goal', $goal->id);

	// How much we need to save for this goal
	$saveAmount = 0;

	if($goal->due) {
		// There is a due date
		// Recalculate the days left, because the due date might have changed if it was due today (see _processDueGoal())
		$daysLeft = floor(($goal->due - $date) / DAY_IN_SECONDS);
		$amountRemaining = $goal->amount - $goal->savings;
		$saveAmount = $amountRemaining / $daysLeft;

		if($amountRemaining > 0) {	
			// Transfer money in
			$transaction = R::dispense('transaction');
			$transaction->goal = $goal;
	    	$transaction->account = SAVINGS_ACCOUNT;
	    	$transaction->amount = $saveAmount;
	    	$transaction->description = "Saving for goal.";
	    	$transaction->time = $date;
	    	R::store($transaction);
		}
	}

	// Reload the goal after the transaction
	// and set its processed date
	// and the daily amount allocated to it
	$goal = R::load('goal', $goal->id);
	$goal->processed = $date;
	$goal->daily = $saveAmount;
	R::store($goal);

	return $saveAmount;
}


function _processDueGoal($goal) {

	$goal = R::load('goal', $goal->id);

	$spendingAccountBalance = $goal->{SPENDING_ACCOUNT};
	$savingsAccountBalance = $goal->{SAVINGS_ACCOUNT};
	// Take the current available and put it to loose change
	// Transfer available to loose change
	
	// Does the goal have unused money in the spending account?
	// send it to the change
	if($spendingAccountBalance != 0) {
		// Take it out of available
		$transaction = R::dispense('transaction');
		$transaction->goal = $goal;
		$transaction->account = SPENDING_ACCOUNT;
		$transaction->amount = 0 - $spendingAccountBalance;
		$transaction->description = "Goal due. Sending any unused available amount to change";
		$transaction->time = $goal->due;
		R::store($transaction);

		$transaction = R::dispense('transaction');
		$transaction->userId = $goal->userId;
		$transaction->account = CHANGE_ACCOUNT;
		$transaction->amount = $spendingAccountBalance;
		$transaction->description = "Goal due. Received surplus available amount from goal.";
		$transaction->time = $goal->due;
		R::store($transaction);
	}
	// Note: Becareful of the order here!
	// Its only in this order, because the way the backend updates on the model
	// doesnt affect it. But it could. Then it should be the other way around.

	// Take it out of saving
	$transaction = R::dispense('transaction');
	$transaction->goal = $goal;
	$transaction->account = SAVINGS_ACCOUNT;
	$transaction->amount = 0 - $savingsAccountBalance;
	$transaction->description = "Goal due. Transferring savings amoun to available";
	$transaction->time = $goal->due;
	R::store($transaction);

	// Save the saving, and put it in available
	// Put it in available
	$transaction = R::dispense('transaction');
	$transaction->goal = $goal;
	$transaction->account = SPENDING_ACCOUNT;
	$transaction->amount = $savingsAccountBalance;
	$transaction->description = "Goal due. Receved savings amount to available.";
	$transaction->time = $goal->due;
	R::store($transaction);

	// Calcualte the next due day
	// Reload the goal, with all those new calculations
	$goal = R::load('goal', $goal->id);
	$goal->due = _calculateDue($goal->due, $goal->repeat);
	R::store($goal);
}

function _calculateDue($date, $repeat) {
	$dateString = date("m/d/y", $date);

	switch($repeat) {
		case 'none':
			$due = null;
			break;
		case 'day':
			$due = strtotime($dateString . " + 1 day");
			break;
		case 'weekday':
			// @todo work out weekday maths
			$due = strtotime($dateString . " + 1 weekday");
			break;
		case 'week':
			$due = strtotime($dateString . " + 1 week");
			break;
		case 'fortnight':
			$due = strtotime($dateString . " + 2 weeks");
			break;
		case 'month':
			$due = strtotime($dateString . " + 1 month");
			break;
		case 'quarter':
			$due = strtotime($dateString . " + 3 months");
			break;
		case 'year':
			$due = strtotime($dateString . " + 1 year");
			break;
	}

	return $due;
}