<?php

namespace App\Service;

use App\Entity\Clinic;
use App\Entity\User;
use App\Entity\UserRole;
use Symfony\Bundle\SecurityBundle\Security;

class ClinicAdminContext
{
    public function __construct(private Security $security)
    {
    }

    /**
     * Returns null for global admins; returns the scoped Clinic for clinic admins.
     */
    public function getClinic(): ?Clinic
    {
        if ($this->isGlobalAdmin()) {
            return null;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        foreach ($user->getUserRoles() as $userRole) {
            /** @var UserRole $userRole */
            if ($userRole->getRole() === 'ROLE_CLINIC_ADMIN' && $userRole->getClinic() !== null) {
                return $userRole->getClinic();
            }
        }

        return null;
    }

    /**
     * True only if the user has ROLE_ADMIN (not clinic-scoped).
     */
    public function isGlobalAdmin(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN')
            && !$this->isClinicOnlyAdmin();
    }

    /**
     * True if the user has ROLE_CLINIC_ADMIN but NOT ROLE_ADMIN directly.
     */
    private function isClinicOnlyAdmin(): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $directRoles = array_map(
            fn(UserRole $ur) => $ur->getRole(),
            $user->getUserRoles()->toArray()
        );

        return !in_array('ROLE_ADMIN', $directRoles, true)
            && in_array('ROLE_CLINIC_ADMIN', $directRoles, true);
    }
}
