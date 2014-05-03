<?php

class Model_Goal extends RedBean_SimpleModel {

    private function recalculate() {
        // Recalcuate how much of the users income is allocated
        $user = R::load('user', $this->bean->userId);

        $dailyIncomeAllocated = R::getCell( 'SELECT SUM(daily) FROM goal WHERE user_id = :user_id AND due > :processedTime', 
            array(':user_id' => $user->id, ':processedTime' => $user->processed)
        );

        $user->dailyIncomeAllocated = $dailyIncomeAllocated;

        R::store($user);
    }

    public function after_update() {
        $this->recalculate();
    }
    public function after_delete() {
        $this->recalculate();
    }
}


class Model_Transaction extends RedBean_SimpleModel {

    private function recalculate() {
        // Goal or change transaction
        if($this->bean->goalId) {
            // Recalculate a goals total
            $goal = R::load('goal', $this->bean->goalId);

            // Recalculate savings account
            if($this->bean->account == SAVINGS_ACCOUNT) {
                // Calculate the total for the savings for the goal
                $savings = R::getCell( 'SELECT SUM(amount) FROM transaction WHERE goal_id = :goal_id AND account = :account', 
                    array(':goal_id' => $goal->id, ':account' => SAVINGS_ACCOUNT)
                );
                if($savings) {
                    $goal->{SAVINGS_ACCOUNT} = $savings;
                } else {
                    $goal->{SAVINGS_ACCOUNT} = 0;
                }

            } else if($this->bean->account == SPENDING_ACCOUNT) {
                // Calculate the total for available for the goal
                $spending = R::getCell( 'SELECT SUM(amount) FROM transaction WHERE goal_id = :goal_id AND account = :account', 
                    array(':goal_id' => $goal->id, ':account' => SPENDING_ACCOUNT)
                );
                if($spending) {
                    $goal->{SPENDING_ACCOUNT} = $spending;
                } else {
                    $goal->{SPENDING_ACCOUNT} = 0;
                }
            }

            R::store($goal);

        } else if($this->bean->userId) {
            // Recalculate a goals total
            $user = R::load('user', $this->bean->userId);

            // Calculate the total for available for the goal
            $change = R::getCell( 'SELECT SUM(amount) FROM transaction WHERE user_id = :user_id AND account = :account', 
              array(':user_id' => $user->id, ':account' => CHANGE_ACCOUNT)
            );

            if($change) {
                $user->{CHANGE_ACCOUNT} = $change;
            } else {
                $user->{CHANGE_ACCOUNT} = 0;
            }

            R::store($user);
        }
    }

    public function after_update() {
        $this->recalculate();
    }
    public function after_delete() {
        $this->recalculate();
    }
}

class Model_User extends RedBean_SimpleModel {
    public function update() {
      // We need to calculate the users daily amount
      $this->bean->dailyIncome = $this->bean->income / $this->bean->incomePeriod;
    }
}