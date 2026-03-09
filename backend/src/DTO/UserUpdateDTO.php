<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserUpdateDTO
{
    #[Assert\NotBlank(message: 'First name is required.')]
    #[Assert\Length(max: 100, maxMessage: 'First name cannot exceed 100 characters.')]
    public string $firstName = '';

    #[Assert\NotBlank(message: 'Last name is required.')]
    #[Assert\Length(max: 100, maxMessage: 'Last name cannot exceed 100 characters.')]
    public string $lastName = '';

    #[Assert\NotBlank(message: 'Username is required.')]
    #[Assert\Length(max: 180, maxMessage: 'Username cannot exceed 180 characters.')]
    #[Assert\Regex(
        pattern: '/^\w+$/',
        message: 'Username can only contain letters, numbers, and underscores.'
    )]
    public string $username = '';
}
