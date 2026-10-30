<?php

namespace App\Enums;

enum RoleCode: string
{
    case SuperAdmin = 'super_admin';
    case Founder = 'founder';
    case Administrative = 'administrative';
    case Instructor = 'instructor';
    case Student = 'student';
}
