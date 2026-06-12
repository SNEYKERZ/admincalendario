<?php

namespace App\Policies;

use App\Models\AbsenceApprovalChain;
use App\Models\User;

class AbsenceApprovalChainPolicy
{
    public function approve(User $user, AbsenceApprovalChain $chain): bool
    {
        return $chain->assigned_to === $user->id && $chain->isPending();
    }

    public function reject(User $user, AbsenceApprovalChain $chain): bool
    {
        return $chain->assigned_to === $user->id && $chain->isPending();
    }
}
