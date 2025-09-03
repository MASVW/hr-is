<?php
namespace App\Support;

class AccessHelper
{
    public static function canAccessHR(): bool
    {
        if (auth()->user()?->isSU()) {
            return true;
        }

        return auth()->check()
            && auth()->user()->isHrDept()
            && (
                auth()->user()->isTeamLeader()
                || auth()->user()->isManager()
                || auth()->user()->isAssMan()
                || auth()->user()->isDirector()
                || auth()->user()->isSPV()
                || auth()->user()->isStaff()
            );
    }
    public static function canViewHR(): bool
    {

        if (auth()->user()?->isSU()) {
            return true;
        }

        return auth()->check()
            && auth()->user()->isHrDept()
            && (
                auth()->user()->isTeamLeader()
                || auth()->user()->isManager()
                || auth()->user()->isAssMan()
                || auth()->user()->isDirector()
                || auth()->user()->isSPV()
                || auth()->user()->isStaff()
            );
    }
    public static function canEditHR(): bool
    {
        if (auth()->user()?->isSU()) {
            return true;
        }

        return auth()->check()
            && auth()->user()->isHrDept()
            && (
                auth()->user()->isTeamLeader()
                || auth()->user()->isManager()
                || auth()->user()->isAssMan()
                || auth()->user()->isDirector()
                || auth()->user()->isSPV()
                || auth()->user()->isStaff()
            );
    }
    public static function canCreateHR(): bool
    {
        if (auth()->user()?->isSU()) {
            return true;
        }

        return auth()->check()
            && auth()->user()->isHrDept()
            && (
                auth()->user()->isTeamLeader()
                || auth()->user()->isManager()
                || auth()->user()->isAssMan()
                || auth()->user()->isDirector()
                || auth()->user()->isSPV()
                || auth()->user()->isStaff()
            );
    }
    public static function canDeleteHR(): bool
    {
        if (auth()->user()?->isSU()) {
            return true;
        }

        return auth()->check()
            && auth()->user()->isHrDept()
            && (
                auth()->user()->isTeamLeader()
                || auth()->user()->isManager()
                || auth()->user()->isAssMan()
                || auth()->user()->isDirector()
                || auth()->user()->isSPV()
            );
    }

    public static function canAccessOnlyStakeHolder(): bool
    {
        if (auth()->user()?->isSU()) {
            return true;
        }
        return auth()->check()
            && (
                auth()->user()->isDirector()
                || (auth()->user()->isHrDept() && auth()->user()->isManager())
            );
    }
    public static function canAccessGlobal(): bool
    {
        if (auth()->user()?->isSU()) {
            return true;
        }
        if (auth()->user()?->isHrDept()) {
            return true;
        }
        return auth()->check()
            && (
                auth()->user()->isTeamLeader()
                || auth()->user()->isManager()
                || auth()->user()->isAssMan()
                || auth()->user()->isDirector()
                || auth()->user()->isSPV()
            );
    }
    public static function canViewGlobal(): bool
    {
        if (auth()->user()?->isSU()) {
            return true;
        }

        return auth()->check()
            && (
                auth()->user()->isTeamLeader()
                || auth()->user()->isManager()
                || auth()->user()->isAssMan()
                || auth()->user()->isDirector()
                || auth()->user()->isSPV()
            );
    }

    public static function canEditGlobal(): bool
    {
        if (auth()->user()?->isSU()) {
            return true;
        }

        return auth()->check()
            && (
                auth()->user()->isManager()
            );
    }
    public static function canDeleteGlobal(): bool
    {
        if (auth()->user()?->isSU()) {
            return true;
        }

        return auth()->check()
            && (
                auth()->user()->isManager()
            );
    }

    public static function canAssignPIC(): bool
    {
        if (auth()->user()?->isSU()) {
            return true;
        }
        return (auth()->user()->isHrDept() && (
                (
                    auth()->user()->isDirector()
                    || auth()->user()->isManager()
                )
                ));
    }
}
