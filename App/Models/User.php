<?php

namespace App\Models;


use DateTime;
use App\Model;

/**
 * Class User
 * @package App\Models
 *
 * @property int id
 * @property string firstName
 * @property string lastName
 * @property string username
 * @property string languageCode
 * @property DateTime createdAt
 */
class User extends Model
{
    public function getFullName()
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
}