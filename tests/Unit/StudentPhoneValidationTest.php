<?php

namespace Tests\Unit;

use App\Models\Student;
use PHPUnit\Framework\TestCase;

class StudentPhoneValidationTest extends TestCase
{
    public function test_accepts_common_ghana_phone_formats(): void
    {
        $this->assertTrue(Student::isValidPhoneInput('0241234567'));
        $this->assertTrue(Student::isValidPhoneInput('+233 24 123 4567'));
        $this->assertTrue(Student::isValidPhoneInput('233241234567'));
        $this->assertTrue(Student::isValidPhoneInput('024-123-4567'));
    }

    public function test_rejects_words_and_invalid_characters(): void
    {
        $this->assertFalse(Student::isValidPhoneInput('hello'));
        $this->assertFalse(Student::isValidPhoneInput('024abc4567'));
        $this->assertFalse(Student::isValidPhoneInput('phone number'));
        $this->assertFalse(Student::isValidPhoneInput('123'));
        $this->assertFalse(Student::isValidPhoneInput(''));
    }
}
